let allAssets = [];
let currentPath = "/";
let uploadFiles = [];

document.addEventListener("DOMContentLoaded", function () {
  loadAssets("/");

  // Drag & Drop Setup
  const dropzone = document.getElementById("dropzone");
  if (dropzone) {
    ["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
      dropzone.addEventListener(eventName, preventDefaults, false);
    });
    ["dragenter", "dragover"].forEach((eventName) => {
      dropzone.addEventListener(eventName, highlight, false);
    });
    ["dragleave", "drop"].forEach((eventName) => {
      dropzone.addEventListener(eventName, unhighlight, false);
    });
    dropzone.addEventListener("drop", handleDrop, false);
  }

  // Search listener
  const searchInput = document.getElementById("search-input");
  if (searchInput) {
    searchInput.addEventListener("keyup", filterAssets);
  }
});

function preventDefaults(e) {
  e.preventDefault();
  e.stopPropagation();
}
function highlight(e) {
  document.getElementById("dropzone").classList.add("dragover");
}
function unhighlight(e) {
  document.getElementById("dropzone").classList.remove("dragover");
}
function handleDrop(e) {
  const dt = e.dataTransfer;
  const files = dt.files;
  handleFileSelect(files);
}

function loadAssets(path) {
  currentPath = path;
  const container = document.getElementById("asset-container");
  container.innerHTML =
    '<div class="loading"><div class="spinner"></div><p>Loading assets...</p></div>';

  const url =
    OC.generateUrl("/apps/nextcloud-microstock-dam/api/list") +
    "?path=" +
    encodeURIComponent(path);

  fetch(url)
    .then((response) => response.json())
    .then((data) => {
      container.innerHTML = "";
      if (!Array.isArray(data) || data.length === 0) {
        container.innerHTML =
          '<div class="loading"><p>No assets found in this folder. Start by uploading one!</p></div>';
        allAssets = [];
        return;
      }
      allAssets = data;
      renderGrid(allAssets);
    })
    .catch((err) => {
      container.innerHTML =
        '<div class="loading"><p>Error loading assets.</p></div>';
      console.error(err);
    });
}

function renderGrid(assets) {
  const container = document.getElementById("asset-container");
  container.innerHTML = "";
  assets.forEach((asset) => {
    const card = document.createElement("div");
    card.className = "asset-card";
    card.innerHTML = `
            <div class="asset-thumb-wrapper">
                <img src="${asset.preview}" class="asset-thumb" onerror="this.src='/core/img/filetypes/folder.svg'">
            </div>
            <div class="asset-details">
                <div class="asset-title">${asset.name}</div>
                <div class="asset-meta">
                    <span>${asset.fileCount} items</span>
                    <span>Folder</span>
                </div>
            </div>
        `;
    card.onclick = () => openModal(asset);
    container.appendChild(card);
  });
}

function filterAssets() {
  const query = document.getElementById("search-input").value.toLowerCase();
  renderGrid(
    allAssets.filter((asset) => asset.name.toLowerCase().includes(query)),
  );
}

function openModal(asset) {
  const modal = document.getElementById("asset-modal");
  modal.style.display = "flex";
  modal.style.opacity = "0";
  setTimeout(() => (modal.style.opacity = "1"), 10);
  document.getElementById("modal-title").innerText = asset.name;
  document.getElementById("modal-count").innerText = asset.fileCount + " items";
  document.getElementById("modal-img").src = asset.preview;
  const downloadUrl =
    OC.generateUrl("/apps/files/ajax/download.php") +
    "?dir=" +
    encodeURIComponent(currentPath) +
    "&files=" +
    encodeURIComponent(asset.name);
  document.getElementById("btn-download-all").href = downloadUrl;
  loadAssetFiles(asset);
}

function loadAssetFiles(asset) {
  const list = document.getElementById("modal-files");
  list.innerHTML = '<div class="loading-small">Fetching files...</div>';
  const assetPath = (currentPath === "/" ? "" : currentPath) + "/" + asset.name;
  const url =
    OC.generateUrl("/apps/nextcloud-microstock-dam/api/files") +
    "?path=" +
    encodeURIComponent(assetPath);
  fetch(url)
    .then((res) => res.json())
    .then((files) => {
      list.innerHTML = "";
      files.forEach((file) => {
        const item = document.createElement("div");
        item.className = "file-item";
        let iconContent = "";
        if (file.preview && file.preview.includes("core/preview")) {
          iconContent = `<img src="${file.preview}">`;
        } else {
          iconContent = `.${file.ext}`;
        }
        item.innerHTML = `
                <div class="file-icon">${iconContent}</div>
                <div class="file-details">
                    <div class="file-name">${file.name}</div>
                    <div class="file-size">${file.size} • ${file.ext.toUpperCase()}</div>
                </div>
                <!-- Add download=1 to ensure correct header, but usually webdav path handles it via browser default if mimetype is known. 
                     Forcing download can be done via 'download' attribute or separate endpoint. -->
                <a href="${file.url}" class="dl-icon" download title="Download"><span class="icon-download"></span></a>
            `;
        item.onclick = (e) => {
          if (e.target.closest("a")) return;
          if (["jpg", "jpeg", "png", "webp", "gif"].includes(file.ext)) {
            document.getElementById("modal-img").src = file.preview.replace(
              "x=400&y=400",
              "x=2000&y=2000",
            );
            document
              .querySelectorAll(".file-item")
              .forEach((el) => el.classList.remove("active"));
            item.classList.add("active");
          }
        };
        list.appendChild(item);
      });
    });
}
function closeModal() {
  document.getElementById("asset-modal").style.display = "none";
}

/* --- Upload Functions --- */

// Explicitly attach these to window so they are available in HTML onclick attributes
window.openUploadModal = function () {
  document.getElementById("upload-modal").style.display = "flex";
  uploadFiles = [];
  renderQueue();
};
window.closeUploadModal = function () {
  document.getElementById("upload-modal").style.display = "none";
};
window.handleFileSelect = function (files) {
  uploadFiles = [...uploadFiles, ...Array.from(files)];
  renderQueue();
  document.getElementById("btn-start-upload").disabled =
    uploadFiles.length === 0;
};
window.startUpload = async function () {
  const folderName = document.getElementById("upload-folder-name").value.trim();
  if (!folderName) {
    alert("Please enter an Asset Title (Folder Name)");
    return;
  }

  if (uploadFiles.length === 0) return;

  const btn = document.getElementById("btn-start-upload");
  btn.disabled = true;
  btn.innerText = "Creating folder...";

  // 1. Create Folder
  const createUrl = OC.generateUrl("/apps/nextcloud-microstock-dam/api/folder");
  let folderPath = "";

  try {
    const createRes = await fetch(createUrl, {
      method: "POST",
      body: new URLSearchParams({ path: "/", name: folderName }),
      headers: { requesttoken: OC.requestToken },
    });
    const createData = await createRes.json();

    if (!createRes.ok && createData.error !== "Folder already exists")
      throw new Error(createData.error);
    folderPath = createData.path || "/" + folderName;

    btn.innerText = "Uploading files...";

    // 2. Upload Files
    const qItems = document.querySelectorAll(".queue-item");

    for (let i = 0; i < uploadFiles.length; i++) {
      const file = uploadFiles[i];
      const qItem = qItems[i];
      const statusSpan = qItem.querySelector("span:last-child");
      statusSpan.innerText = "Uploading...";

      const formData = new FormData();
      formData.append("file", file);

      const uploadUrl =
        OC.generateUrl("/apps/nextcloud-microstock-dam/api/upload") +
        "?path=" +
        encodeURIComponent(folderPath);

      try {
        const upRes = await fetch(uploadUrl, {
          method: "POST",
          body: formData,
          headers: { requesttoken: OC.requestToken },
        });

        if (upRes.ok) {
          statusSpan.innerText = "Done";
          statusSpan.className = "status-success";
        } else {
          const err = await upRes.json();
          statusSpan.innerText = "Error: " + err.error;
          statusSpan.className = "status-error";
        }
      } catch (e) {
        statusSpan.innerText = "Failed";
        statusSpan.className = "status-error";
      }
    }

    btn.innerText = "Upload Complete";
    setTimeout(() => {
      closeUploadModal();
      loadAssets("/");
      btn.innerText = "Upload Asset";
      btn.disabled = false;
    }, 1500);
  } catch (err) {
    alert("Error creating asset: " + err.message);
    btn.disabled = false;
    btn.innerText = "Upload Asset";
  }
};
window.closeModal = closeModal;
