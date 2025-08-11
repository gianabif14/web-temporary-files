document.addEventListener('DOMContentLoaded', function() {
    
    // --- Preview Nama File Sebelum Upload ---
    const fileInput = document.getElementById('filesToUpload');
    const fileListPreview = document.getElementById('file-list-preview');

    if (fileInput) {
        fileInput.addEventListener('change', function() {
            fileListPreview.innerHTML = '';
            if (this.files.length > 0) {
                const list = document.createElement('ul');
                Array.from(this.files).forEach(file => {
                    const listItem = document.createElement('li');
                    listItem.textContent = `File: ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)`;
                    list.appendChild(listItem);
                });
                fileListPreview.appendChild(list);
            }
        });
    }
    
    // --- Fungsi Countdown Waktu Kedaluwarsa ---
    function startCountdown() {
        const countdownElements = document.querySelectorAll('.countdown');
        
        // Cek hanya jika ada elemen countdown di halaman
        if(countdownElements.length === 0) return;

        setInterval(function() {
            const now = Math.floor(Date.now() / 1000); // Waktu saat ini dalam detik

            countdownElements.forEach(el => {
                const expiryTimestamp = parseInt(el.getAttribute('data-expiry-timestamp'), 10);
                
                // Jika elemen sudah tidak ada di DOM, lewati
                if (!el.parentNode) return;

                const timeLeft = expiryTimestamp - now;

                if (timeLeft > 0) {
                    const minutes = Math.floor(timeLeft / 60);
                    const seconds = timeLeft % 60;
                    el.textContent = `${minutes}m ${seconds.toString().padStart(2, '0')}s`;
                } else {
                    el.textContent = 'Kedaluwarsa';
                    const fileItem = el.closest('.file-item');
                    if (fileItem) {
                        // Sembunyikan elemen secara perlahan untuk efek visual
                        fileItem.style.transition = 'opacity 0.5s ease';
                        fileItem.style.opacity = '0';
                        setTimeout(() => fileItem.style.display = 'none', 500);
                    }
                }
            });
        }, 1000);
    }

    // Panggil fungsi countdown
    startCountdown();
});