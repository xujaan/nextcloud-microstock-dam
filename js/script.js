(function () {
  console.log("Stock app loaded");

  // Global variable to hold upload queue
  window.uploadFiles = [];

  window.loadAssets = function (path) {
    if (!path) path = "/";
    const grid = document.getElementById("asset-grid");
    if (!grid) return;

    grid.innerHTML = '<div class="loading"></div>';

    const url = OC.generateUrl("/apps/nextcloud-microstock-dam/api/list");

    fetch(url + "?path=" + encodeURIComponent(path))
      .then((response) => response.json())
      .then((data) => {
        grid.innerHTML = "";
        if (data.error) {
          grid.innerHTML = `<div class="error">${data.error}</div>`;
          return;
        }

        if (data.length === 0) {
          grid.innerHTML = `<div class="empty-state">No assets found in this folder.</div>`;
          return;
        }

        data.forEach((item) => {
          const card = document.createElement("div");
          card.className = "asset-card";
          card.onclick = () => window.openAsset(item);

          const previewUrl = item.preview;

          card.innerHTML = `
                        <div class="asset-preview" style="background-image: url('${previewUrl}')">
                            <span class="file-count-badge">${item.fileCount} items</span>
                        </div>
                        <div class="asset-info">
                            <div class="asset-name">${item.name}</div>
                        </div>
                    `;
          grid.appendChild(card);
        });
      })
      .catch((err) => {
        console.error(err);
        grid.innerHTML = '<div class="error">Failed to load assets</div>';
      });
  };

  window.openAsset = function (asset) {
    const modal = document.getElementById("asset-modal");
    const modalContent = modal.querySelector(".modal-content-body");
    const modalTitle = document.getElementById("modal-title");

    modalTitle.textContent = asset.name;
    modal.classList.add("active");
    modalContent.innerHTML = '<div class="loading">Loading details...</div>';

    // Fetch files in folder
    const url = OC.generateUrl("/apps/nextcloud-microstock-dam/api/files");
    fetch(url + "?path=" + encodeURIComponent(asset.path))
      .then((res) => res.json())
      .then((files) => {
        if (files.error) {
          modalContent.innerHTML = `<div class="error">${files.error}</div>`;
          return;
        }

        let fileListHtml = `<div class="file-list">`;
        files.forEach((f) => {
          fileListHtml += `
                        <div class="file-item">
                            <img src="${f.preview}" class="file-icon">
                            <div class="file-details">
                                <div class="file-name">${f.name}</div>
                                <div class="file-meta">${f.size} • ${f.ext}</div>
                            </div>
                            <a href="${f.url}" download class="btn-icon" title="Download">⬇</a>
                        </div>
                    `;
        });
        fileListHtml += `</div>`;

        // Construct Layout
        modalContent.innerHTML = `
                    <div class="modal-split">
                        <div class="modal-preview-large">
                            <img src="${asset.preview}" class="hero-image">
                        </div>
                        <div class="modal-metadata">
                            <h3>Files</h3>
                            ${fileListHtml}
                            <div class="modal-actions">
                                <button class="btn btn-primary" onclick="alert('Zip download logic to be implemented via WebDAV')">Download Package (ZIP)</button>
                            </div>
                        </div>
                    </div>
                `;
      })
      .catch((err) => {
        modalContent.innerHTML = `<div class="error">Error loading files</div>`;
      });
  };

  window.closeModal = function () {
    document.getElementById("asset-modal").classList.remove("active");
  };

  // Upload Logic
  window.openUploadModal = function () {
    document.getElementById("upload-modal").classList.add("active");
  };

  window.closeUploadModal = function () {
    document.getElementById("upload-modal").classList.remove("active");
    window.uploadFiles = [];
    window.renderQueue();
  };

  window.handleFileSelect = function (files) {
    window.uploadFiles = [...window.uploadFiles, ...Array.from(files)];
    window.renderQueue();
  };

  window.renderQueue = function () {
    const queue = document.getElementById("upload-queue");
    queue.innerHTML = "";
    window.uploadFiles.forEach((file, index) => {
      const item = document.createElement("div");
      item.className = "queue-item";
      item.innerHTML = `
                <span>${file.name}</span>
                <span onclick="window.uploadFiles.splice(${index}, 1); window.renderQueue();" style="cursor:pointer; color: red;">×</span>
             `;
      queue.appendChild(item);
    });

    // Enable/Disable start button
    const btn = document.getElementById("btn-start-upload");
    if (btn) btn.disabled = window.uploadFiles.length === 0;
  };

  window.startUpload = async function () {
    const folderName = document.getElementById("new-folder-name").value;
    if (!folderName) {
      alert("Please enter a folder name");
      return;
    }

    // 1. Create Folder
    const createUrl = OC.generateUrl(
      "/apps/nextcloud-microstock-dam/api/folder",
    );
    const formData = new FormData();
    formData.append("path", "/"); // Root for now
    formData.append("name", folderName);

    try {
      const res = await fetch(createUrl, { method: "POST", body: formData });
      const data = await res.json();

      if (res.status !== 200) throw new Error(data.error);

      const targetPath = data.path; // e.g. "MyNewAsset"

      // 2. Upload Files
      const uploadUrl = OC.generateUrl(
        "/apps/nextcloud-microstock-dam/api/upload",
      );

      for (const file of window.uploadFiles) {
        const fData = new FormData();
        fData.append("file", file);
        // We pass path as query param or body? ApiController expects query param 'path' usually or we adjust logic
        // My ApiController says: uploadFile(string $path)
        // Nextcloud routing passes attributes from URL usually.
        // Wait, in my routes.php: /api/upload -> uploadFile.
        // The ApiController::uploadFile expects $path argument.
        // Request params are mapped to arguments. So I should append path to FormData or QueryString.
        // Safest is QueryString for this controller setup.

        await fetch(uploadUrl + "?path=" + encodeURIComponent(targetPath), {
          method: "POST",
          body: fData,
        });
      }

      alert("Upload Complete!");
      window.closeUploadModal();
      window.loadAssets("/");
    } catch (e) {
      alert("Error: " + e.message);
    }
  };

  document.addEventListener("DOMContentLoaded", function () {
    window.loadAssets("/");
  });
})();
