<?php
// Load Script and Style via Controller or here if needed, but PageController does it.
?>

<div id="app-content">
    <div id="app-navigation">
        <div class="nav-header">
            <h2>Stock</h2>
        </div>
        <ul class="with-icon">
            <li><a href="#" class="active" onclick="window.loadAssets && window.loadAssets('/'); return false;">All Assets</a></li>
            <li><a href="#" onclick="window.openUploadModal(); return false;">Upload New Asset</a></li>
        </ul>
    </div>

    <div id="app-content-wrapper">
        <div class="app-content-list" style="padding: 20px;">
            <div id="asset-grid" class="asset-grid">
                <!-- Assets loaded via JS -->
            </div>
        </div>
    </div>
</div>

<!-- Modal for Asset Details -->
<div id="asset-modal" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h2 id="modal-title">Asset Name</h2>
            <button class="icon-close" onclick="window.closeModal()"></button>
        </div>
        <div class="modal-content-body">
            <!-- Loaded via JS -->
        </div>
    </div>
</div>

<!-- Modal for Upload -->
<div id="upload-modal" class="modal-overlay">
    <div class="modal-container" style="max-width: 500px;">
        <div class="modal-header">
            <h2>Upload New Asset</h2>
            <button class="icon-close" onclick="window.closeUploadModal()"></button>
        </div>
        <div class="modal-content-body" style="padding: 20px;">
            <div class="form-group">
                <label>Asset Name (Folder Name)</label>
                <input type="text" id="new-folder-name" class="form-control" placeholder="e.g. Vintage-Car-2024">
            </div>
            <br>
            <div class="upload-area" onclick="document.getElementById('file-input').click()">
                <p>Click or Drag files here</p>
                <input type="file" id="file-input" multiple style="display:none" onchange="window.handleFileSelect(this.files)">
            </div>
            <div id="upload-queue"></div>
            <br>
            <button id="btn-start-upload" class="primary" disabled onclick="window.startUpload()">Start Upload</button>
        </div>
    </div>
</div>
