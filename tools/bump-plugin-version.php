<?php
/**
 * Update plugin metadata before packaging a release artifact.
 *
 * @package SmartHybridCache
 */

if ( $argc < 2 ) {
	fwrite( STDERR, "Usage: php tools/bump-plugin-version.php <version>\n" );
	exit( 1 );
}

$version = ltrim( trim( (string) $argv[1] ), 'v' );
if ( ! preg_match( '/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version ) ) {
	fwrite( STDERR, "Invalid version '{$version}'. Expected semantic version like 1.2.3.\n" );
	exit( 1 );
}

$root        = dirname( __DIR__ );
$plugin_file = $root . '/smart-hybrid-cache/smart-hybrid-cache.php';
$readme_file = $root . '/smart-hybrid-cache/readme.txt';

replace_in_file(
	$plugin_file,
	array(
		'/^ \* Version:\s*.+$/m' => ' * Version: ' . $version,
		"/define\( 'SMART_HYBRID_CACHE_VERSION', '[^']+' \);/" => "define( 'SMART_HYBRID_CACHE_VERSION', '" . $version . "' );",
	)
);

replace_in_file(
	$readme_file,
	array(
		'/^Stable tag:\s*.+$/m' => 'Stable tag: ' . $version,
	)
);

echo "Bumped Smart Hybrid Cache metadata to {$version}\n";

/**
 * Replace expected patterns in a file.
 *
 * @param string $file         Absolute file path.
 * @param array  $replacements Map of regex patterns to replacement strings.
 */
function replace_in_file( string $file, array $replacements ): void {
	$contents = file_get_contents( $file );
	if ( false === $contents ) {
		fwrite( STDERR, "Unable to read {$file}\n" );
		exit( 1 );
	}

	foreach ( $replacements as $pattern => $replacement ) {
		$updated = preg_replace( $pattern, $replacement, $contents, 1, $count );
		if ( null === $updated || 1 !== $count ) {
			fwrite( STDERR, "Unable to update {$file} with pattern {$pattern}\n" );
			exit( 1 );
		}
		$contents = $updated;
	}

	if ( false === file_put_contents( $file, $contents ) ) {
		fwrite( STDERR, "Unable to write {$file}\n" );
		exit( 1 );
	}
}
