/**
 * DBP Music Hub - Bulk Upload JavaScript
 */

(function($) {
	'use strict';

	const DBPBulkUpload = {
		uploader: null,
		uploadedFiles: [],
		settings: {},

		/**
		 * Initialize
		 */
		init: function() {
			this.initPlupload();
			this.bindEvents();
		},

		/**
		 * Initialize Plupload
		 */
		initPlupload: function() {
			this.uploader = new plupload.Uploader(dbpBulkUpload.pluploadConfig);
			
			this.uploader.bind('Init', this.onInit.bind(this));
			this.uploader.bind('FilesAdded', this.onFilesAdded.bind(this));
			this.uploader.bind('UploadProgress', this.onUploadProgress.bind(this));
			this.uploader.bind('FileUploaded', this.onFileUploaded.bind(this));
			this.uploader.bind('Error', this.onError.bind(this));
			this.uploader.bind('UploadComplete', this.onUploadComplete.bind(this));
			
			this.uploader.init();
		},

		/**
		 * Bind events
		 */
		bindEvents: function() {
			$('#dbp-start-upload').on('click', this.startUpload.bind(this));
			$('#dbp-cancel-upload').on('click', this.cancelUpload.bind(this));
			$('#dbp-upload-more').on('click', this.uploadMore.bind(this));
			
			// Drag & Drop events
			const $dropZone = $('#dbp-drop-zone');
			$dropZone.on('dragover', function(e) {
				e.preventDefault();
				$(this).addClass('dragover');
			});
			$dropZone.on('dragleave', function() {
				$(this).removeClass('dragover');
			});
			$dropZone.on('drop', function() {
				$(this).removeClass('dragover');
			});
		},

		/**
		 * Plupload: Init
		 */
		onInit: function(up) {
			console.log('Plupload initialized');
		},

		/**
		 * Plupload: Files added
		 */
		onFilesAdded: function(up, files) {
			$('#dbp-drop-zone').hide();
			$('#dbp-upload-queue').show();
			
			// Update total count
			$('.dbp-progress-total').text(up.files.length);
			
			// Add files to list
			files.forEach(file => {
				this.addFileToList(file);
				this.extractID3Tags(file);
			});
		},

		/**
		 * Add file to list
		 */
		addFileToList: function(file) {
			const $fileItem = $(`
				<li class="dbp-file-item" data-id="${file.id}">
					<div class="dbp-file-icon">
						<span class="dashicons dashicons-format-audio"></span>
					</div>
					<div class="dbp-file-info">
						<span class="dbp-file-name">${file.name}</span>
						<span class="dbp-file-size">${plupload.formatSize(file.size)}</span>
						<span class="dbp-file-status pending">Wartet...</span>
					</div>
					<div class="dbp-file-progress">
						<div class="dbp-file-progress-fill" style="width: 0%"></div>
					</div>
					<div class="dbp-file-actions">
						<button type="button" class="button button-small dbp-remove-file" data-id="${file.id}">
							Entfernen
						</button>
					</div>
				</li>
			`);
			
			$fileItem.find('.dbp-remove-file').on('click', () => {
				this.uploader.removeFile(file);
				$fileItem.remove();
				this.updateProgress();
			});
			
			$('#dbp-file-list').append($fileItem);
		},

		/**
		 * Extract ID3 tags from file
		 */
		extractID3Tags: function(file) {
			// Use jsmediatags library
			if (typeof jsmediatags === 'undefined') {
				return;
			}
			
			const reader = new FileReader();
			reader.onload = (e) => {
				jsmediatags.read(e.target.result, {
					onSuccess: (tag) => {
						// Store ID3 tags with file
						file.id3Tags = {
							title: tag.tags.title || '',
							artist: tag.tags.artist || '',
							album: tag.tags.album || '',
							year: tag.tags.year || '',
							genre: tag.tags.genre || ''
						};
						
						// Update file name display if title exists
						if (tag.tags.title) {
							$(`[data-id="${file.id}"] .dbp-file-name`).text(tag.tags.title);
						}
					},
					onError: (error) => {
						console.log('ID3 read error:', error);
					}
				});
			};
			reader.readAsArrayBuffer(file.getNative());
		},

		/**
		 * Start upload
		 */
		startUpload: function() {
			this.settings = {
				genre: $('#default-genre').val(),
				category: $('#default-category').val(),
				price: $('#default-price').val(),
				license: $('#default-license').val(),
				create_product: $('#auto-create-product').is(':checked') ? '1' : '0'
			};
			
			$('#dbp-start-upload').prop('disabled', true);
			this.uploader.start();
		},

		/**
		 * Cancel upload
		 */
		cancelUpload: function() {
			this.uploader.stop();
			$('#dbp-start-upload').prop('disabled', false);
		},

		/**
		 * Upload more files
		 */
		uploadMore: function() {
			$('#dbp-upload-success').hide();
			$('#dbp-drop-zone').show();
			$('#dbp-upload-queue').hide();
			$('#dbp-file-list').empty();
			this.uploadedFiles = [];
			this.uploader.splice();
			$('.dbp-progress-fill').css('width', '0%');
			$('.dbp-progress-current').text('0');
			$('.dbp-progress-total').text('0');
		},

		/**
		 * Plupload: Upload progress
		 */
		onUploadProgress: function(up, file) {
			const $fileItem = $(`[data-id="${file.id}"]`);
			$fileItem.removeClass('pending').addClass('uploading');
			$fileItem.find('.dbp-file-status').removeClass('pending').addClass('uploading').text('Uploading...');
			$fileItem.find('.dbp-file-progress-fill').css('width', file.percent + '%');
			
			this.updateProgress();
		},

		/**
		 * Plupload: File uploaded
		 */
		onFileUploaded: function(up, file, response) {
			const $fileItem = $(`[data-id="${file.id}"]`);
			const result = JSON.parse(response.response);
			
			if (result.success) {
				$fileItem.removeClass('uploading').addClass('success');
				$fileItem.find('.dbp-file-status').removeClass('uploading').addClass('success').text('✓ Erfolgreich');
				this.uploadedFiles.push(result.data);
			} else {
				$fileItem.removeClass('uploading').addClass('error');
				$fileItem.find('.dbp-file-status').removeClass('uploading').addClass('error').text('✗ Fehler');
			}
			
			this.updateProgress();
		},

		/**
		 * Plupload: Error
		 */
		onError: function(up, err) {
			console.error('Upload error:', err);
			const $fileItem = $(`[data-id="${err.file.id}"]`);
			$fileItem.removeClass('uploading').addClass('error');
			$fileItem.find('.dbp-file-status').removeClass('uploading').addClass('error').text('✗ Fehler: ' + err.message);
		},

		/**
		 * Plupload: Upload complete
		 */
		onUploadComplete: function() {
			$('#dbp-upload-queue').hide();
			$('#dbp-upload-success').show();
			
			const successCount = this.uploadedFiles.length;
			const totalCount = this.uploader.files.length;
			
			$('.dbp-success-message').text(
				`${successCount} von ${totalCount} Dateien erfolgreich hochgeladen.`
			);
			
			$('#dbp-start-upload').prop('disabled', false);
		},

		/**
		 * Update progress
		 */
		updateProgress: function() {
			const total = this.uploader.files.length;
			const uploaded = this.uploader.files.filter(f => f.status === plupload.DONE).length;
			
			$('.dbp-progress-current').text(uploaded);
			$('.dbp-progress-total').text(total);
			
			const percent = total > 0 ? (uploaded / total) * 100 : 0;
			$('.dbp-progress-fill').css('width', percent + '%');
		},

		/**
		 * Before upload (add metadata)
		 */
		beforeUpload: function(up, file) {
			// Add ID3 tags and settings to multipart params
			const params = Object.assign({}, this.settings);
			
			if (file.id3Tags) {
				Object.assign(params, file.id3Tags);
			}
			
			up.settings.multipart_params = Object.assign(
				up.settings.multipart_params,
				params
			);
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		if ($('.dbp-bulk-upload-page').length) {
			DBPBulkUpload.init();
		}
	});

})(jQuery);
