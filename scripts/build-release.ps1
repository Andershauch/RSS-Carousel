param(
	[string]$PluginSlug = 'rss-news-carousel',
	[string]$MainPluginFile = 'news-topic-carousel.php'
)

$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$mainPluginPath = Join-Path $projectRoot $MainPluginFile

if ( -not ( Test-Path -LiteralPath $mainPluginPath ) ) {
	throw "Main plugin file not found: $mainPluginPath"
}

$pluginFileContents = Get-Content -LiteralPath $mainPluginPath -Raw

if ( $pluginFileContents -notmatch 'Version:\s*([0-9]+\.[0-9]+\.[0-9]+)' ) {
	throw 'Unable to determine plugin version from the main plugin file.'
}

$pluginVersion = $Matches[1]
$distPath      = Join-Path $projectRoot 'dist'
$zipPath       = Join-Path $distPath ( $PluginSlug + '-' + $pluginVersion + '.zip' )
$buildItems    = @(
	'assets',
	'includes',
	'CHANGELOG.md',
	'LICENSE.txt',
	'news-topic-carousel.php',
	'readme.txt',
	'uninstall.php'
)

if ( -not ( Test-Path -LiteralPath $distPath ) ) {
	New-Item -ItemType Directory -Path $distPath | Out-Null
}

if ( Test-Path -LiteralPath $zipPath ) {
	Remove-Item -LiteralPath $zipPath -Force
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$zipStream = [System.IO.File]::Open( $zipPath, [System.IO.FileMode]::CreateNew )
$archive   = $null

try {
	$archive = New-Object System.IO.Compression.ZipArchive(
		$zipStream,
		[System.IO.Compression.ZipArchiveMode]::Create,
		$false
	)

	foreach ( $item in $buildItems ) {
		$fullPath = Join-Path $projectRoot $item

		if ( Test-Path -LiteralPath $fullPath -PathType Container ) {
			Get-ChildItem -LiteralPath $fullPath -Recurse -File | ForEach-Object {
				$relativePath = $_.FullName.Substring( $projectRoot.Length + 1 ).Replace( '\', '/' )
				$entryName    = $PluginSlug + '/' + $relativePath

				[System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
					$archive,
					$_.FullName,
					$entryName,
					[System.IO.Compression.CompressionLevel]::Optimal
				) | Out-Null
			}
		} elseif ( Test-Path -LiteralPath $fullPath -PathType Leaf ) {
			$entryName = $PluginSlug + '/' + $item.Replace( '\', '/' )

			[System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
				$archive,
				$fullPath,
				$entryName,
				[System.IO.Compression.CompressionLevel]::Optimal
			) | Out-Null
		}
	}
} finally {
	if ( $null -ne $archive ) {
		$archive.Dispose()
	}

	$zipStream.Dispose()
}

Write-Output "Created release package: $zipPath"
