<?php if (!defined('IN_GS')) { die('You cannot load this page directly.'); }

/**
 * ImageEditor - Admin grid page
 */

global $SITEURL;

// ── Path handling (mirrors filebrowser.php's convention: $_GET['path'] already
//    carries a trailing slash when set, e.g. "gallery/") ──────────────────────
$subPath = isset($_GET['path']) ? $_GET['path'] : '';
$path    = GSDATAUPLOADPATH . $subPath;

if (!path_is_safe($path, GSDATAUPLOADPATH)) {
	die('Invalid path.');
}

$imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

$dirsArray  = [];
$filesArray = [];
$dircount   = 0;
$count      = 0;
$totalsize  = 0;

$filenames = getFiles($path);
if (count($filenames) != 0) {
	foreach ($filenames as $file) {
		if ($file == '.' || $file == '..' || $file == '.htaccess') {
			continue;
		} elseif (is_dir($path . $file)) {
			$dirsArray[$dircount]['name'] = $file;
			$dircount++;
		} else {
			$ext = strtolower(substr($file, strrpos($file, '.') + 1));
			if (!in_array($ext, $imageExts, true)) continue; // images only

			$filesArray[$count]['name'] = $file;
			clearstatcache();
			$ss = @stat($path . $file);
			$filesArray[$count]['date'] = @date('M j, Y', $ss['mtime']);
			$filesArray[$count]['size'] = fSize($ss['size']);
			$totalsize += $ss['size'];
			$count++;
		}
	}
}
$filesSorted = subval_sort($filesArray, 'name');
$dirsSorted  = subval_sort($dirsArray, 'name');

$pathParts = explode('/', trim($subPath, '/'));
$urlPath   = '';
?>
<div class="gie-wrap">
	<div class="gie-header">
		<h3><svg xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle" width="24" height="24" viewBox="0 0 48 48"><path d="M0 0h48v48H0z" fill="none"/><path fill="#8CBCD6" d="M31 41H8c-2.2 0-4-1.8-4-4V11c0-2.2 1.8-4 4-4h32c2.2 0 4 1.8 4 4v17c0 7.2-5.8 13-13 13"/><circle cx="35" cy="16" r="3" fill="#B3DDF5"/><path fill="#9AC9E3" d="M20 16L9 32h22z"/><path fill="#B3DDF5" d="m31 22l-8 10h16z"/><path fill="#E57373" d="m47.7 29.1l-2.8-2.8c-.4-.4-1.1-.4-1.6 0L42 27.6l4.4 4.4l1.3-1.3c.4-.4.4-1.1 0-1.6"/><path fill="#FF9800" d="M27.467 42.167L39.77 29.865l4.384 4.384L31.85 46.55z"/><path fill="#B0BEC5" d="m46.4 32.038l-2.192 2.192l-4.383-4.384l2.192-2.191z"/><path fill="#FFC107" d="M27.5 42.2L26 48l5.8-1.5z"/><path fill="#37474F" d="m26.7 45l-.7 3l3-.7z"/></svg> <?= i18n_r('ImageEditor/lang_Menu_Title') ?: 'Image Editor' ?></h3>
		<p><?= i18n_r('ImageEditor/lang_Description') ?: 'Browse images and edit them.' ?></p>
		<div class="gie-breadcrumb">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 48 48"><rect width="48" height="48" fill="none"></rect><path fill="#ffa000" d="M40 12H22l-4-4H8c-2.2 0-4 1.8-4 4v8h40v-4c0-2.2-1.8-4-4-4"></path><path fill="#ffca28" d="M40 12H8c-2.2 0-4 1.8-4 4v20c0 2.2 1.8 4 4 4h32c2.2 0 4-1.8 4-4V16c0-2.2-1.8-4-4-4"></path></svg> <a href="?id=<?= ImageEditor_SLUG ?>"><?= htmlspecialchars(i18n_r('ImageEditor/lang_Breadcrumb_Root')) ?></a>
<?php
	foreach ($pathParts as $part) {
		if ($part === '') continue;
		$urlPath .= $part . '/';
		echo '<span class="gie-sep">/</span><a href="?id=' . ImageEditor_SLUG . '&amp;path=' . rawurlencode($urlPath) . '">' . htmlspecialchars($part) . '</a>';
	}
?>
		</div>
	</div>

<?php
	$hasDirs  = count($dirsSorted) > 0;
	$hasFiles = count($filesSorted) > 0;

	if (!$hasDirs && !$hasFiles) {
		echo '<div class="gie-empty">' . htmlspecialchars(i18n_r('ImageEditor/lang_No_Images')) . '</div>';
	} else {
		echo '<div class="gie-grid">';

		if ($hasDirs) {
			foreach ($dirsSorted as $dir) {
				$linkPath = $subPath . $dir['name'] . '/';
				echo '<a class="gie-card gie-card-folder" href="?id=' . ImageEditor_SLUG . '&amp;path=' . rawurlencode($linkPath) . '" title="' . htmlspecialchars($dir['name']) . '">';
				echo '<div class="gie-card-thumb"><svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 48 48"><rect width="48" height="48" fill="none"/><path fill="#ffa000" d="M40 12H22l-4-4H8c-2.2 0-4 1.8-4 4v8h40v-4c0-2.2-1.8-4-4-4"/><path fill="#ffca28" d="M40 12H8c-2.2 0-4 1.8-4 4v20c0 2.2 1.8 4 4 4h32c2.2 0 4-1.8 4-4V16c0-2.2-1.8-4-4-4"/></svg></div>';
				echo '<div class="gie-card-body"><div class="gie-card-name">' . htmlspecialchars($dir['name']) . '</div></div>';
				echo '</a>';
			}
		}

		if ($hasFiles) {
			foreach ($filesSorted as $img) {
				$name    = $img['name'];
				$encName = rawurlencode($name);

				$thumbRel = $subPath . 'thumbnail.' . $name;
				if (file_exists(GSTHUMBNAILPATH . $thumbRel)) {
					$thumbUrl = $SITEURL . 'data/thumbs/' . $subPath . 'thumbnail.' . $encName;
				} else {
					$thumbUrl = $SITEURL . 'data/uploads/' . $subPath . $encName;
				}

				echo '<div class="gie-card">';
				echo '<div class="gie-card-thumb"><img src="' . $thumbUrl . '" loading="lazy" alt="" title="' . htmlspecialchars($name) . '"></div>';
				echo '<div class="gie-card-body">';
				echo '<div class="gie-card-name">' . htmlspecialchars($name) . '</div>';
				echo '<div class="gie-card-meta"><span>' . $img['size'] . '</span><span>' . $img['date'] . '</span></div>';
				$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
				$editLabel = htmlspecialchars(i18n_r('ImageEditor/lang_Edit'));
				if ($ext === 'svg') {
					echo '<button type="button" class="gie-edit-btn" disabled title="' . htmlspecialchars(i18n_r('ImageEditor/lang_SVG_Not_Supported')) . '">' . $editLabel . '</button>';
				} else {
					$fullUrl = preg_replace('#^https?://#i', '//', $SITEURL) . 'data/uploads/' . $subPath . $encName;
					echo '<button type="button" class="gie-edit-btn" data-name="' . htmlspecialchars($name) . '" data-src="' . htmlspecialchars($fullUrl) . '" onclick="gieOpenEditor(this)">' . $editLabel . '</button>';
				}
				echo '</div>';
				echo '</div>';
			}
		}

		echo '</div>'; // .gie-grid
	}

	$imageWord = ($count == 1) ? i18n_r('ImageEditor/lang_Image_Singular') : i18n_r('ImageEditor/lang_Image_Plural');
	echo '<p class="gie-footer"><em><b>' . $count . '</b> ' . htmlspecialchars($imageWord) . ' (' . fSize($totalsize) . ')</em></p>';
?>
</div>

<link rel="stylesheet" href="<?= ImageEditor_PLUGIN_URL ?>tui/tui-color-picker.min.css">
<link rel="stylesheet" href="<?= ImageEditor_PLUGIN_URL ?>tui/tui-image-editor.css">
<script src="<?= ImageEditor_PLUGIN_URL ?>tui/tui-color-picker.min.js"></script>
<script src="<?= ImageEditor_PLUGIN_URL ?>tui/tui-image-editor.min.js"></script>

<!-- Google Fonts -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto&family=Open+Sans&family=Lato&family=Montserrat&family=Oswald&family=Poppins&family=Merriweather&family=Playfair+Display&family=Raleway&family=Nunito&family=Inter&family=PT+Sans&family=Ubuntu&family=Rubik&family=Work+Sans&family=Noto+Sans&family=Roboto+Slab&family=Bebas+Neue&family=Caveat&family=Dancing+Script&family=Pacifico&family=Roboto+Mono&family=Fira+Code&display=swap">

<div class="gie-modal" id="gieModal">
	<div class="gie-modal-inner">
		<div class="gie-modal-topbar">
			<h3><svg xmlns="http://www.w3.org/2000/svg" style="vertical-align:middle" width="24" height="24" viewBox="0 0 48 48"><path d="M0 0h48v48H0z" fill="none"/><path fill="#8CBCD6" d="M31 41H8c-2.2 0-4-1.8-4-4V11c0-2.2 1.8-4 4-4h32c2.2 0 4 1.8 4 4v17c0 7.2-5.8 13-13 13"/><circle cx="35" cy="16" r="3" fill="#B3DDF5"/><path fill="#9AC9E3" d="M20 16L9 32h22z"/><path fill="#B3DDF5" d="m31 22l-8 10h16z"/><path fill="#E57373" d="m47.7 29.1l-2.8-2.8c-.4-.4-1.1-.4-1.6 0L42 27.6l4.4 4.4l1.3-1.3c.4-.4.4-1.1 0-1.6"/><path fill="#FF9800" d="M27.467 42.167L39.77 29.865l4.384 4.384L31.85 46.55z"/><path fill="#B0BEC5" d="m46.4 32.038l-2.192 2.192l-4.383-4.384l2.192-2.191z"/><path fill="#FFC107" d="M27.5 42.2L26 48l5.8-1.5z"/><path fill="#37474F" d="m26.7 45l-.7 3l3-.7z"/></svg> <?= htmlspecialchars(i18n_r('ImageEditor/lang_Modal_Title')) ?></h3>
			<span id="gieModalTitle"></span>
			<div class="gie-modal-actions">
				<label class="gie-save-field">
					<?= htmlspecialchars(i18n_r('ImageEditor/lang_Name')) ?>
					<input type="text" id="gieFilenameInput" class="gie-name-input">
				</label>
				<label class="gie-save-field">
					<?= htmlspecialchars(i18n_r('ImageEditor/lang_Type')) ?>
					<select id="gieFormatSelect">
						<option value="png">PNG</option>
						<option value="jpeg">JPG</option>
						<option value="webp">WebP</option>
					</select>
				</label>
				<span id="gieFormatHint" class="gie-format-hint"></span>
				<button type="button" id="gieSaveBtn" class="gie-btn gie-btn-primary"><?= htmlspecialchars(i18n_r('ImageEditor/lang_Save_As_Copy')) ?></button>
				<button type="button" id="gieCancelBtn" class="gie-btn"><?= htmlspecialchars(i18n_r('ImageEditor/lang_Cancel')) ?></button>
			</div>
		</div>
		<div id="gieTuiContainer"></div>
		<div id="gieSaveStatus" class="gie-save-status"></div>
	</div>
</div>

<script>
(function () {
	var IE_AJAX_URL  = '?id=<?= ImageEditor_SLUG ?>&path=<?= rawurlencode($subPath) ?>';
	var IE_SUB_PATH  = <?= json_encode($subPath) ?>;
	var IE_I18N      = <?= json_encode([
		'webpSafariNote' => i18n_r('ImageEditor/lang_js_Webp_Safari_Note'),
		'loadingImage'   => i18n_r('ImageEditor/lang_js_Loading_Image'),
		'couldNotLoad'   => i18n_r('ImageEditor/lang_js_Could_Not_Load'),
		'enterFilename'  => i18n_r('ImageEditor/lang_js_Enter_Filename'),
		'saving'         => i18n_r('ImageEditor/lang_js_Saving'),
		'saveUnexpected' => i18n_r('ImageEditor/lang_js_Save_Unexpected'),
		'savedAs'        => i18n_r('ImageEditor/lang_js_Saved_As'),
		'saveFailed'     => i18n_r('ImageEditor/lang_js_Save_Failed'),
		'unknownError'   => i18n_r('ImageEditor/lang_js_Unknown_Error'),
		'networkError'   => i18n_r('ImageEditor/lang_js_Network_Error'),
		'fontFamily'     => i18n_r('ImageEditor/lang_Font_Family'),
	]) ?>;
	var tuiInstance  = null;
	var currentName  = null;

	function gieUpdateFormatHint() {
		var hint = document.getElementById('gieFormatHint');
		var fmt  = document.getElementById('gieFormatSelect').value;
		hint.textContent = (fmt === 'webp') ? IE_I18N.webpSafariNote : '';
	}
	document.getElementById('gieFormatSelect').addEventListener('change', gieUpdateFormatHint);

	// Keep this list in sync with the Google Fonts <link> above.
	var GIE_GOOGLE_FONTS = [
		'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Oswald',
		'Poppins', 'Merriweather', 'Playfair Display', 'Raleway', 'Nunito',
		'Inter', 'PT Sans', 'Ubuntu', 'Rubik', 'Work Sans',
		'Noto Sans', 'Roboto Slab', 'Bebas Neue', 'Caveat', 'Dancing Script',
		'Pacifico', 'Roboto Mono', 'Fira Code'
	];

	function gieDefaultLockAspectRatio() {
		var checkbox = document.querySelector('.tie-lock-aspect-ratio');
		if (!checkbox || checkbox.checked) return;
		checkbox.checked = true;
		checkbox.dispatchEvent(new Event('change'));
	}

	function gieInjectFontPicker(instance) {
		var submenu = document.querySelector('.tui-image-editor-menu-text');
		if (!submenu) return; // text submenu markup not found - skip quietly

		var optionsHtml = '';
		GIE_GOOGLE_FONTS.forEach(function (font) {
			optionsHtml += '<option value="' + font + '" style="font-family:\'' + font + '\'">' + font + '</option>';
		});

		var row = document.createElement('div');
		row.className = 'gie-font-row';
		row.innerHTML = '<select class="gie-font-selector">' + optionsHtml + '</select>';
		submenu.insertBefore(row, submenu.firstChild);

		var selector = row.querySelector('.gie-font-selector');
		selector.title = IE_I18N.fontFamily;
		var activeId = null;

		selector.addEventListener('change', function () {
			if (activeId === null) return;
			var font = selector.value;

			var applyFont = function () {
				instance.changeTextStyle(activeId, { fontFamily: font }).catch(function () {});
			};
			if (window.FontFace && document.fonts && document.fonts.load) {
				document.fonts.load('16px "' + font + '"').then(applyFont).catch(applyFont);
			} else {
				applyFont();
			}
		});

		instance.on('objectActivated', function (props) {
			activeId = props.id;
			if (props.type === 'text' && props.fontFamily) {
				selector.value = props.fontFamily;
			}
		});
	}

	window.gieOpenEditor = function (btn) {
		var name = btn.getAttribute('data-name');
		var src  = btn.getAttribute('data-src');
		currentName = name;

		// Default the filename field to "<original>-edit"
		var dotIdx  = name.lastIndexOf('.');
		var base    = dotIdx > -1 ? name.substring(0, dotIdx) : name;
		var origExt = dotIdx > -1 ? name.substring(dotIdx + 1).toLowerCase() : '';
		document.getElementById('gieFilenameInput').value = base + '-edit';
		document.getElementById('gieFormatSelect').value =
			(origExt === 'jpg' || origExt === 'jpeg') ? 'jpeg' :
			(origExt === 'webp') ? 'webp' : 'png';
		gieUpdateFormatHint();

		document.getElementById('gieModalTitle').textContent = name;
		document.getElementById('gieSaveStatus').textContent = IE_I18N.loadingImage;
		document.getElementById('gieModal').classList.add('gie-open');

		if (tuiInstance) {
			tuiInstance.destroy();
			tuiInstance = null;
		}
		
		fetch(src)
			.then(function (r) {
				if (!r.ok) throw new Error('HTTP ' + r.status);
				return r.blob();
			})
			.then(function (blob) {
				return new Promise(function (resolve, reject) {
					var reader = new FileReader();
					reader.onload  = function () { resolve(reader.result); };
					reader.onerror = reject;
					reader.readAsDataURL(blob);
				});
			})
			.then(function (dataUrl) {
				document.getElementById('gieSaveStatus').textContent = '';
				// Wait a frame so the modal has actually laid out (non-zero
				// width/height) before Toast UI measures its container.
				requestAnimationFrame(function () {
					tuiInstance = new tui.ImageEditor(document.getElementById('gieTuiContainer'), {
						includeUI: {
							loadImage: { path: dataUrl, name: name },
							theme: {},
							menuBarPosition: 'left'
						},
						cssMaxWidth: 1100,
						cssMaxHeight: 650,
						usageStatistics: false
					});
					gieInjectFontPicker(tuiInstance);
					gieDefaultLockAspectRatio();
				});
			})
			.catch(function (err) {
				document.getElementById('gieSaveStatus').textContent = IE_I18N.couldNotLoad + err.message;
			});
	};

	function closeEditor() {
		document.getElementById('gieModal').classList.remove('gie-open');
		if (tuiInstance) {
			tuiInstance.destroy();
			tuiInstance = null;
		}
		currentName = null;
	}

	document.getElementById('gieCancelBtn').addEventListener('click', closeEditor);

	document.getElementById('gieSaveBtn').addEventListener('click', function () {
		if (!tuiInstance || !currentName) return;

		var statusEl = document.getElementById('gieSaveStatus');
		var format   = document.getElementById('gieFormatSelect').value; // 'png' | 'jpeg'
		var filename = document.getElementById('gieFilenameInput').value.trim();

		if (!filename) {
			statusEl.textContent = IE_I18N.enterFilename;
			return;
		}

		statusEl.textContent = IE_I18N.saving;

		var dataUrl = tuiInstance.toDataURL({ format: format, quality: 0.92 });

		var fd = new FormData();
		fd.append('ie_action', 'save_copy');
		fd.append('path', IE_SUB_PATH);
		fd.append('original_name', currentName);
		fd.append('filename', filename);
		fd.append('image_data', dataUrl);

		fetch(IE_AJAX_URL, { method: 'POST', body: fd })
			.then(function (r) { return r.text(); })
			.then(function (text) {
				var resp;
				try { resp = JSON.parse(text); } catch (e) {
					statusEl.textContent = IE_I18N.saveUnexpected;
					return;
				}
				if (resp.ok) {
					statusEl.textContent = IE_I18N.savedAs.replace('{name}', resp.name);
					setTimeout(function () { window.location.reload(); }, 600);
				} else {
					statusEl.textContent = IE_I18N.saveFailed.replace('{msg}', resp.msg || IE_I18N.unknownError);
				}
			})
			.catch(function () {
				statusEl.textContent = IE_I18N.networkError;
			});
	});
})();
</script>

<style>
.gie-modal{display:none;position:fixed;inset:0;background:rgba(20,20,30,.55);z-index:9999;align-items:center;justify-content:center;padding:1.5rem}
.gie-modal.gie-open{display:flex}
.gie-modal-inner{background:#fff;border-radius:10px;max-width:1320px;width:100%;max-height:92vh;overflow:auto;padding:1rem;box-shadow:0 12px 40px rgba(0,0,0,.3)}
.gie-modal-topbar{display:flex;align-items:center;justify-content:space-between;gap:1rem;margin-bottom:.75rem;font-weight:600;color:#1a1a2e;flex-wrap:wrap}
.gie-modal-topbar h3{margin:0; font-family: Arial, Helvetica, sans-serif; font-weight:600; font-style: normal;}
.gie-modal-actions{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap}
.gie-save-field{display:flex;align-items:center;gap:.35rem;font-size:.78rem;font-weight:600;color:#6b7280}
.gie-format-hint{font-size:.72rem;color:#e07b00;font-weight:400;max-width:220px}
.gie-name-input{padding:.35rem .5rem;font-size:.82rem;font-weight:400;color:#1a1a2e;border:1px solid #d0d5dd;border-radius:6px;width:160px}
#gieFormatSelect{padding:.35rem .4rem;font-size:.82rem;font-weight:400;color:#1a1a2e;border:1px solid #d0d5dd;border-radius:6px}
.gie-btn{padding:.45rem .9rem;font-size:.85rem;font-weight:600;border-radius:6px;border:1px solid #d0d5dd;background:#f4f6f9;color:#1a1a2e;cursor:pointer}
.gie-btn:hover{background:#e9ecf2}
.gie-btn-primary{background:#2764e7;border-color:#2764e7;color:#fff}
.gie-btn-primary:hover{background:#0094f0}
.gie-save-status{margin-top:.5rem;font-size:.8rem;color:#6b7280;min-height:1.2em}
#gieTuiContainer{width:100%;min-height:600px}
.gie-font-row{display:block;width:100%;padding:.4rem .6rem;box-sizing:border-box;border-bottom:1px solid #444}
.gie-font-selector{width:100%;padding:.3rem .4rem;font-size:.8rem;border-radius:4px;border:1px solid #555;background:#2a2a2a;color:#fff}
.gie-wrap{font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;color:#1a1a2e;padding:.25rem 0 1rem}
.gie-header h3{display:flex;align-items:center;gap:.5rem;font-size:1.15rem;margin:0 0 .75rem; font-family: Arial, Helvetica, sans-serif; font-weight:600; font-style: normal;}
.gie-breadcrumb{display:flex;flex-wrap:wrap;align-items:center;gap:.25rem;font-size:.875rem;padding:.6rem .9rem;background:#fff;border:1px solid #e2e5ea;border-radius:10px;margin-bottom:1rem}
.gie-breadcrumb a{color:#2764e7;text-decoration:none;font-weight:600;padding:.15rem .35rem;border-radius:5px}
.gie-breadcrumb a:hover{background:#eef3ff}
.gie-sep{color:#6b7280}
.gie-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:.75rem}
.gie-card{position:relative;display:flex;flex-direction:column;background:#fff;border:1px solid #e2e5ea;border-radius:10px;text-decoration:none;color:#1a1a2e;transition:box-shadow .15s,transform .15s,border-color .15s}
.gie-card:hover{box-shadow:0 6px 18px rgba(20,30,60,.09);transform:translateY(-2px);border-color:#c9d6f5}
.gie-card-thumb{width:100%;aspect-ratio:4/3;background:#f0f2f6;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:10px 10px 0 0}
.gie-card-folder .gie-card-thumb{background:#fff8e6}
.gie-card-thumb img{width:100%;height:100%;object-fit:cover;display:block}
.gie-card-body{padding:.55rem .65rem .65rem;display:flex;flex-direction:column;gap:.3rem}
.gie-card-name{font-size:.8rem;font-weight:600;line-height:1.25;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;text-align: center}
.gie-card-meta{font-size:.7rem;color:#6b7280;display:flex;justify-content:space-between;gap:.4rem}
.gie-edit-btn{margin-top:.15rem;padding:.35rem .6rem;font-size:.75rem;font-weight:600;border-radius:6px;border:1px solid #d0d5dd;background:#f4f6f9;color:#2764e7;cursor:pointer}
.gie-edit-btn:hover{background:#eef3ff;border-color:#c9d6f5}
.gie-edit-btn:disabled{color:#9ca3af;cursor:not-allowed}
.gie-edit-btn:disabled:hover{background:#f4f6f9;border-color:#d0d5dd}
.gie-empty,.gie-footer{color:#6b7280}
.gie-empty{padding:2rem 1rem;text-align:center;font-size:.9rem;background:#fff;border:1px solid #e2e5ea;border-radius:10px}
.gie-footer{margin-top:1rem;font-size:.8rem}
.tui-image-editor-header{display:none}
.tui-image-editor-container .tui-image-editor-main {top: 20px;}
.tui-image-editor-container .tui-image-editor-help-menu.right {right: 25px;}
.wrapper a:link, .wrapper a:visited {text-decoration: none;}
</style>
