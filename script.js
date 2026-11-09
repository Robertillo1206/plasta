let photos = [];
let currentIndex = 0;

const grid = document.getElementById('photo-grid');
const emptyState = document.getElementById('empty-state');
const fileInput = document.getElementById('file-input');
const lightbox = document.getElementById('lightbox');
const lightboxImg = document.getElementById('lightbox-img');
const loadingOverlay = document.getElementById('loading-overlay');

window.onload = loadPhotos;

// Cargar fotos desde el servidor
async function loadPhotos() {
    try {
        const res = await fetch('api.php?action=get');
        photos = await res.json();
        renderGrid();
    } catch (err) {
        console.error("Error al cargar", err);
    }
}

// Pintar la cuadrícula
function renderGrid() {
    grid.innerHTML = '';
    if (photos.length === 0) {
        emptyState.classList.remove('hidden');
    } else {
        emptyState.classList.add('hidden');
        photos.forEach((photo, index) => {
            const div = document.createElement('div');
            div.className = 'grid-item';
            div.innerHTML = `<img src="uploads/${photo.filename}" loading="lazy">`;
            div.onclick = () => openLightbox(index);
            grid.appendChild(div);
        });
    }
}

// Escuchar cuando el usuario selecciona fotos
fileInput.addEventListener('change', async (e) => {
    const files = e.target.files;
    if (files.length === 0) return;

    loadingOverlay.classList.remove('hidden');
    const formData = new FormData();
    formData.append('action', 'upload');
    
    for (let i = 0; i < files.length; i++) {
        formData.append('photos[]', files[i]);
    }

    try {
        const response = await fetch('api.php', { method: 'POST', body: formData });
        const textResponse = await response.text(); // Leemos la respuesta cruda (texto/html)

        try {
            // Intentamos convertirla al formato correcto (JSON)
            const data = JSON.parse(textResponse); 
            if (data.error) {
                alert("❌ Error del servidor: " + data.error);
            } else {
                fileInput.value = ''; // Limpiar el input
                await loadPhotos();   // Recargar las fotos
            }
        } catch (parseError) {
            // Si falla, significa que el hosting ha devuelto una página de error
            alert("❌ El servidor ha bloqueado la subida. Respuesta del hosting:\n\n" + textResponse.substring(0, 150) + "...");
            console.error("Respuesta completa:", textResponse);
        }

    } catch (err) {
        alert('❌ Error de red. Comprueba tu conexión a internet.');
    } finally {
        loadingOverlay.classList.add('hidden');
    }
});

// Visor
function openLightbox(index) {
    currentIndex = index;
    lightboxImg.src = `uploads/${photos[currentIndex].filename}`;
    lightbox.classList.remove('hidden');
}

function closeLightbox() {
    lightbox.classList.add('hidden');
}

// Borrar
async function deleteCurrentPhoto() {
    if (!confirm('¿Borrar esta foto definitivamente?')) return;
    
    const photoId = photos[currentIndex].id;
    try {
        await fetch(`api.php?action=delete&id=${photoId}`);
        closeLightbox();
        await loadPhotos();
    } catch (err) {
        alert('Error al borrar');
    }
}