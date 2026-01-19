<?php
script('nextcloud-microstock-dam', 'script');
style('nextcloud-microstock-dam', 'style');
?>

<div id="app-content">
    <div id="app-navigation">
        <div class="nav-header">
            <h2>Assets</h2>
        </div>
        <ul class="with-icon">
            <li><a href="#" class="active" onclick="window.loadAssets && window.loadAssets('/'); return false;">All Assets</a></li>
        </ul>
    </div>

    <div id="app-content-wrapper">
        <div class="top-bar">
            <div class="search-box">
                <span class="icon-search icon-white"></span>
                <input type="text" id="search-input" placeholder="Search assets...">
            </div>
            <div class="actions">
                <button class="btn primary-btn" onclick="openUploadModal()">
                    <span class="icon-add icon-white"></span> Upload Asset
                </button>
            </div>
        </div>

        <div class="assets-grid" id="asset-container">
            <div class="loading">
                <div class="spinner"></div>
                <p>Loading your collection...</p>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div id="upload-modal" class="modal-overlay" style="display:none;">
    <div class="modal-backdrop" onclick="closeUploadModal()"></div>
    <div class="modal-content modal-small">
        <button class="close-btn" onclick="closeUploadModal()">
            <span class="icon-close"></span>
        </button>
        
        <div class="modal-body-column">
            <div class="modal-title-bar">
                <h3>New Asset Upload</h3>
            </div>
            
            <div class="upload-form">
                <div class="form-group">
                    <label>Asset Title (Folder Name)</label>
                    <input type="text" id="upload-folder-name" placeholder="e.g. Logo Redesign 2024" class="form-control">
                </div>
                
                <div class="dropzone" id="dropzone">
                    <div class="dropzone-content">
                        <span class="icon-upload-cloud"></span>
                        <p>Drag and drop files here to upload</p>
                        <button class="btn secondary-btn" onclick="document.getElementById('file-input').click()">Browse Files</button>
                        <input type="file" id="file-input" multiple style="display:none" onchange="handleFileSelect(this.files)">
                    </div>
                </div>

                <div class="file-queue" id="file-queue">
                    <!-- Pending files go here -->
                </div>
                
                <div class="upload-actions">
                    <button class="btn primary-btn" id="btn-start-upload" onclick="startUpload()" disabled>Upload Asset</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Asset Viewer Modal -->
<div id="asset-modal" class="modal-overlay" style="display:none;">
    <div class="modal-backdrop" onclick="closeModal()"></div>
    <div class="modal-content">
        <button class="close-btn" onclick="closeModal()">
            <span class="icon-close"></span>
        </button>
        
        <div class="modal-body">
            <div class="modal-left">
                <div class="preview-container" id="preview-container">
                    <img id="modal-img" src="" alt="Preview">
                </div>
            </div>
            
            <div class="modal-right">
                <div class="modal-header">
                    <h2 id="modal-title">Asset Title</h2>
                    <span class="badge" id="modal-count">0 items</span>
                </div>
                
                <div class="modal-actions">
                    <a href="#" id="btn-download-all" class="btn primary-btn">
                        <span class="icon-download icon-white"></span> Download Package (ZIP)
                    </a>
                </div>

                <div class="file-list-header">
                    <span>Included Files</span>
                </div>
                
                <div class="file-list" id="modal-files">
                    <div class="loading-small">Loading files...</div>
                </div>
            </div>
        </div>
    </div>
</div>
