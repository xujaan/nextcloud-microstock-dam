(function () {
  console.log("Stock App: Loading...");

  // Constants
  const APP_ID = "stock";
  const API_BASE = "/apps/" + APP_ID + "/api";

  // State
  window.stockApp = {
    uploadQueue: [],
    currentPath: "/",
  };

  // Helper to generate URL
  function getApiUrl(endpoint) {
    return OC.generateUrl(API_BASE + endpoint);
  }

  // --- Core Functions ---

  window.loadAssets = function (path) {
    console.log("Stock App: Loading assets for path:", path);

    path = path || "/";
    window.stockApp.currentPath = path;

    const grid = document.getElementById("asset-grid");
    if (!grid) {
      console.error("Stock App: #asset-grid not found!");
      return;
    }

    grid.innerHTML = '<div class="loading-spinner"></div>';

    const url = getApiUrl("/list");

    fetch(url + "?path=" + encodeURIComponent(path))
      .then(async (response) => {
        const data = await response.json();
        if (!response.ok) {
          throw new Error(data.error || "HTTP " + response.status);
        }
        return data;
      })
      .then((data) => {
        grid.innerHTML = "";

        if (data.error) {
          throw new Error(data.error);
        }

        if (!Array.isArray(data)) {
          throw new Error("Invalid response format");
        }

        if (data.length === 0) {
          grid.innerHTML = `<div class="empty-state">
                        <div class="empty-icon">📂</div>
                        <h3>No assets found</h3>
                        <p>Upload a folder or files to get started.</p>
                     </div>`;
          return;
        }

        data.forEach((item) => {
          const card = document.createElement("div");
          card.className = "asset-card";
          card.onclick = () => window.openAsset(item);

          const previewUrl = item.preview || "/core/img/filetypes/folder.svg";

          card.innerHTML = `
                        <div class="asset-preview" style="background-image: url('${previewUrl}')">
                            <span class="file-count-badge">${item.fileCount} items</span>
                        </div>
                        <div class="asset-info">
                            <div class="asset-name" title="${item.name}">${item.name}</div>
                        </div>
                    `;
          grid.appendChild(card);
        });
      })
      .catch((err) => {
        console.error("Stock App: Asset load error:", err);
        grid.innerHTML = `<div class="error-state">
                    <h3>Error loading assets</h3>
                    <p>${err.message}</p>
                </div>`;
      });
  };

  window.openAsset = function (asset) {
    console.log("Stock App: Opening asset", asset);
    const modal = document.getElementById("asset-modal");
    const modalContent = modal.querySelector(".modal-content-body");
    const modalTitle = document.getElementById("modal-title");

    modalTitle.textContent = asset.name;
    modal.classList.add("active");
    modalContent.innerHTML = '<div class="loading-spinner"></div>';

    const url = getApiUrl("/files");
    fetch(url + "?path=" + encodeURIComponent(asset.path))
      .then((res) => res.json())
      .then((files) => {
        if (files.error) throw new Error(files.error);

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

        modalContent.innerHTML = `
                    <div class="modal-split">
                        <div class="modal-preview-large">
                            <img src="${asset.preview}" class="hero-image">
                        </div>
                        <div class="modal-metadata">
                            <h3>Files</h3>
                            ${fileListHtml}
                           <!-- <div class="modal-actions">
                                <button class="btn btn-primary">Download ZIP (Coming Soon)</button>
                            </div> -->
                        </div>
                    </div>
                `;
      })
      .catch((err) => {
        modalContent.innerHTML = `<div class="error-message">Error: ${err.message}</div>`;
      });
  };

  window.closeModal = function () {
    document
      .querySelectorAll(".modal-overlay")
      .forEach((el) => el.classList.remove("active"));
  };

  // --- Upload Logic ---

  window.openUploadModal = function () {
    document.getElementById("upload-modal").classList.add("active");
  };

  window.closeUploadModal = function () {
    document.getElementById("upload-modal").classList.remove("active");
    window.stockApp.uploadQueue = [];
    window.renderQueue();
  };

  window.handleFileSelect = function (files) {
    window.stockApp.uploadQueue = [
      ...window.stockApp.uploadQueue,
      ...Array.from(files),
    ];
    window.renderQueue();
  };

  window.renderQueue = function () {
    const queue = document.getElementById("upload-queue");
    if (!queue) return;

    queue.innerHTML = "";
    window.stockApp.uploadQueue.forEach((file, index) => {
      const item = document.createElement("div");
      item.className = "queue-item";
      item.innerHTML = `
                <span class="filename">${file.name}</span>
                <span class="remove-btn" onclick="window.stockApp.uploadQueue.splice(${index}, 1); window.renderQueue();">×</span>
             `;
      queue.appendChild(item);
    });

    const btn = document.getElementById("btn-start-upload");
    if (btn) btn.disabled = window.stockApp.uploadQueue.length === 0;
  };

  window.startUpload = async function () {
    const folderName = document.getElementById("new-folder-name").value;
    if (!folderName) {
      alert("Please enter a folder name");
      return;
    }

    const btn = document.getElementById("btn-start-upload");
    btn.disabled = true;
    btn.textContent = "Creating Folder...";

    try {
      // 1. Create Folder
      const createUrl = getApiUrl("/folder");
      const formData = new FormData();
      formData.append("path", "/");
      formData.append("name", folderName);

      const res = await fetch(createUrl, { method: "POST", body: formData });
      const data = await res.json();

      if (data.error) throw new Error(data.error);

      const targetPath = data.path; // e.g. "Folder"

      // 2. Upload Files
      btn.textContent = "Uploading Files...";
      const uploadUrl = getApiUrl("/upload");

      let successCount = 0;
      for (const file of window.stockApp.uploadQueue) {
        const fData = new FormData();
        fData.append("file", file);

        // Append path to query string to ensure Controller sees it
        await fetch(uploadUrl + "?path=" + encodeURIComponent(targetPath), {
          method: "POST",
          body: fData,
        });
        successCount++;
        btn.textContent = `Uploading ${successCount}/${window.stockApp.uploadQueue.length}...`;
      }

      alert("Upload Complete!");
      window.closeUploadModal();
      window.loadAssets("/");
    } catch (e) {
      console.error(e);
      alert("Upload Failed: " + e.message);
    } finally {
      btn.disabled = false;
      btn.textContent = "Start Upload";
    }
  };

  // Initialize
  document.addEventListener("DOMContentLoaded", function () {
    console.log("Stock App: DOM Loaded");
    window.loadAssets("/");
  });
})();
