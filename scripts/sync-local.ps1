param(
	[string]$LocalSitePath = 'C:\Users\ander\Local Sites\whitehartdanes\app\public\wp-content\plugins\rss-news-carousel'
)

$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$itemsToSync = @(
	'assets',
	'includes',
	'CHANGELOG.md',
	'LICENSE.txt',
	'news-topic-carousel.php',
	'readme.txt',
	'uninstall.php'
)

if ( -not ( Test-Path -LiteralPath $LocalSitePath ) ) {
	New-Item -ItemType Directory -Path $LocalSitePath -Force | Out-Null
}

foreach ( $item in $itemsToSync ) {
	$sourcePath      = Join-Path $projectRoot $item
	$destinationPath = Join-Path $LocalSitePath $item

	if ( -not ( Test-Path -LiteralPath $sourcePath ) ) {
		continue
	}

	if ( Test-Path -LiteralPath $sourcePath -PathType Container ) {
		if ( -not ( Test-Path -LiteralPath $destinationPath ) ) {
			New-Item -ItemType Directory -Path $destinationPath -Force | Out-Null
		}

		Get-ChildItem -LiteralPath $sourcePath -Force | ForEach-Object {
			Copy-Item -LiteralPath $_.FullName -Destination $destinationPath -Recurse -Force
		}
		continue
	}

	$destinationDirectory = Split-Path -Parent $destinationPath

	if ( -not ( Test-Path -LiteralPath $destinationDirectory ) ) {
		New-Item -ItemType Directory -Path $destinationDirectory -Force | Out-Null
	}

	Copy-Item -LiteralPath $sourcePath -Destination $destinationPath -Force
}

Write-Output "Synced plugin files to: $LocalSitePath"
