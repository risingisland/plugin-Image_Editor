<?php if (!defined('IN_GS')) { die('You cannot load this page directly.'); }

/*
Plugin Name: Image Editor
Description: Browse uploaded images and edit them with a canvas-based editor. Saves edits as new copies in data/uploads.
Version: 1.0
Author: risingisland
Author URI: https://ko-fi.com/ericmontgomery
*/

i18n_merge('ImageEditor') || i18n_merge('ImageEditor', 'en_US');

// ─── Constants ───────────────────────────────────────────────────────────────
define('ImageEditor_SLUG',       basename(__FILE__, '.php'));
define('ImageEditor_PLUGIN_DIR', GSPLUGINPATH . ImageEditor_SLUG . '/');
define('ImageEditor_PLUGIN_URL', $SITEURL . 'plugins/' . ImageEditor_SLUG . '/');

// ─── Bootstrap ───────────────────────────────────────────────────────────────
register_plugin(
	ImageEditor_SLUG,
	'Image Editor',
	'1.0',
	'risingisland',
	'https://ko-fi.com/ericmontgomery',
	'Browse uploaded images and edit them with a canvas-based editor. Saves edits as new copies in data/uploads.',
	'files',
	'ImageEditor_admin_page'
);

add_action('files-sidebar', 'createSideMenu', array(
	ImageEditor_SLUG,
	i18n_r('ImageEditor/lang_Menu_Title') . ' <svg xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle" width="22" height="22" viewBox="0 0 48 48"><path d="M0 0h48v48H0z" fill="none"/><path fill="#8CBCD6" d="M31 41H8c-2.2 0-4-1.8-4-4V11c0-2.2 1.8-4 4-4h32c2.2 0 4 1.8 4 4v17c0 7.2-5.8 13-13 13"/><circle cx="35" cy="16" r="3" fill="#B3DDF5"/><path fill="#9AC9E3" d="M20 16L9 32h22z"/><path fill="#B3DDF5" d="m31 22l-8 10h16z"/><path fill="#E57373" d="m47.7 29.1l-2.8-2.8c-.4-.4-1.1-.4-1.6 0L42 27.6l4.4 4.4l1.3-1.3c.4-.4.4-1.1 0-1.6"/><path fill="#FF9800" d="M27.467 42.167L39.77 29.865l4.384 4.384L31.85 46.55z"/><path fill="#B0BEC5" d="m46.4 32.038l-2.192 2.192l-4.383-4.384l2.192-2.191z"/><path fill="#FFC107" d="M27.5 42.2L26 48l5.8-1.5z"/><path fill="#37474F" d="m26.7 45l-.7 3l3-.7z"/></svg>',
	'?id=' . ImageEditor_SLUG
));

if (
	!empty($_REQUEST['ie_action']) &&
	isset($_GET['id']) && $_GET['id'] === ImageEditor_SLUG
) {
	ImageEditor_handle_ajax($_REQUEST['ie_action']);
	// ImageEditor_handle_ajax() always exit()s - this is a safety net:
	exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// AJAX ENDPOINTS
// ─────────────────────────────────────────────────────────────────────────────

function ImageEditor_handle_ajax($action) {
	header('Content-Type: application/json');

	switch ($action) {

		case 'save_copy': {
			$subPath  = isset($_POST['path']) ? $_POST['path'] : '';
			$origName = isset($_POST['original_name']) ? basename($_POST['original_name']) : '';
			$wantName = isset($_POST['filename']) ? trim($_POST['filename']) : '';
			$dataUrl  = $_POST['image_data'] ?? '';

			$destDir = GSDATAUPLOADPATH . $subPath;

			if (!path_is_safe($destDir, GSDATAUPLOADPATH) || $origName === '' || $dataUrl === '') {
				echo json_encode(['ok' => false, 'msg' => 'Invalid request.']);
				exit;
			}

			// Expect a data: URL like "data:image/png;base64,..."
			if (!preg_match('/^data:image\/(png|jpeg|webp);base64,(.+)$/', $dataUrl, $m)) {
				echo json_encode(['ok' => false, 'msg' => 'Unexpected image data format.']);
				exit;
			}
			$extMap = ['png' => 'png', 'jpeg' => 'jpg', 'webp' => 'webp'];
			$ext    = $extMap[$m[1]];
			$binary = base64_decode($m[2], true);
			if ($binary === false) {
				echo json_encode(['ok' => false, 'msg' => 'Could not decode image data.']);
				exit;
			}

			$base = $wantName !== '' ? basename($wantName) : pathinfo($origName, PATHINFO_FILENAME) . '-edit';
			$base = preg_replace('/[^A-Za-z0-9_\-]+/', '-', $base);
			$base = trim($base, '-_');
			if ($base === '') $base = pathinfo($origName, PATHINFO_FILENAME) . '-edit';

			// Build a unique filename in case one already exists.
			$newName = $base . '.' . $ext;
			$i       = 1;
			while (file_exists($destDir . $newName)) {
				$newName = $base . '-' . $i . '.' . $ext;
				$i++;
			}

			if (file_put_contents($destDir . $newName, $binary) === false) {
				echo json_encode(['ok' => false, 'msg' => 'Could not write file.']);
				exit;
			}

			if (function_exists('filepath_is_safe') && !filepath_is_safe($destDir . $newName, $destDir)) {
				@unlink($destDir . $newName);
				echo json_encode(['ok' => false, 'msg' => 'Invalid resulting path.']);
				exit;
			}

			@chmod($destDir . $newName, 0644);

			$relFile = $subPath . $newName;

			$thumbsmOk = false;
			$classFile = GSADMININCPATH . 'image.class.php';
			if (file_exists($classFile)) {
				require_once $classFile;
				if (class_exists('Zubrag_image')) {
					$destThumbDir = dirname(GSTHUMBNAILPATH . $relFile);
					if (!is_dir($destThumbDir)) @mkdir($destThumbDir, 0755, true);
					try {
						$img = new Zubrag_image();
						$img->max_x        = 65;
						$img->max_y        = 130;
						$img->quality      = 75;
						$img->save_to_file = true;
						$img->image_type   = -1;
						$img->GenerateThumbFile(GSDATAUPLOADPATH . $relFile, GSTHUMBNAILPATH . $subPath . 'thumbsm.' . $newName);
						$thumbsmOk = true;
					} catch (\Throwable $e) {
						// non-fatal: grid falls back to the full image
					}
				}
			}

			$thumbLargeOk = false;
			if (!function_exists('genStdThumb')) {
				$incFile = GSADMININCPATH . 'imagemanipulation.php';
				if (file_exists($incFile)) require_once $incFile;
			}
			if (function_exists('genStdThumb')) {
				try {
					genStdThumb($subPath, $newName);
					$thumbLargeOk = true;
				} catch (\Throwable $e) {
					// non-fatal
				}
			}

			echo json_encode(['ok' => true, 'name' => $newName, 'thumbsm' => $thumbsmOk, 'thumbnail' => $thumbLargeOk]);
			exit;
		}

		default:
			echo json_encode(['ok' => false, 'msg' => 'Unknown action']);
			exit;
	}
}

// ─────────────────────────────────────────────────────────────────────────────
// ADMIN PAGE  (entry point called by GS)
// ─────────────────────────────────────────────────────────────────────────────

function ImageEditor_admin_page() {
	require ImageEditor_PLUGIN_DIR . 'editor.php';
}
