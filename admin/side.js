// (Opsional) Fungsi untuk toggle sidebar pada perangkat mobile
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggleBtn = document.getElementById('sidebarToggle');
    if (sidebarToggleBtn) {
      sidebarToggleBtn.addEventListener('click', function() {
        const sidebar = document.querySelector('aside');
        // Menggunakan Tailwind utility untuk menyembunyikan/memperlihatkan sidebar
        sidebar.classList.toggle('-translate-x-full');
      });
    }
  });

  document.addEventListener('DOMContentLoaded', () => {
    const hamburger = document.getElementById('hamburger');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');

    // Fungsi toggle sidebar
    const toggleSidebar = () => {
      sidebar.classList.toggle('active');
      overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
      console.log('Sidebar aktif:', sidebar.classList.contains('active'));
    };

    // Event listener untuk klik tombol hamburger (toggle sidebar)
    hamburger.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleSidebar();
    });

    // Mencegah klik di dalam sidebar menutupnya
    sidebar.addEventListener('click', (e) => {
      e.stopPropagation();
    });

    // Listener global: jika klik di luar hamburger dan sidebar (untuk perangkat mobile)
    document.addEventListener('click', (e) => {
      if (window.innerWidth < 768) {
        if (!sidebar.contains(e.target) && !hamburger.contains(e.target)) {
          if (sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            overlay.style.display = 'none';
            console.log('Sidebar ditutup karena klik di luar.');
          }
        }
      }
    });

    // Jika window di-resize ke lebar desktop, pastikan sidebar tertutup
    window.addEventListener('resize', () => {
      if (window.innerWidth >= 768) {
        if (sidebar.classList.contains('active')) {
          sidebar.classList.remove('active');
          overlay.style.display = 'none';
          console.log('Sidebar ditutup karena resize ke desktop.');
        }
      }
    });

    // Draggable functionality untuk tombol hamburger dengan batas agar tidak keluar dari viewport
    let isDragging = false;
    let startX = 0,
      startY = 0;
    let offsetX = 0,
      offsetY = 0;

    // Fungsi untuk menghitung batas maksimum (viewport - ukuran tombol)
    const getMaxOffsets = () => {
      const maxX = window.innerWidth - hamburger.offsetWidth;
      const maxY = window.innerHeight - hamburger.offsetHeight;
      return {
        maxX,
        maxY
      };
    };

    // Untuk perangkat touch
    hamburger.addEventListener('touchstart', (e) => {
      isDragging = true;
      const touch = e.touches[0];
      startX = touch.clientX - offsetX;
      startY = touch.clientY - offsetY;
    }, {
      passive: false
    });

    hamburger.addEventListener('touchmove', (e) => {
      if (isDragging) {
        e.preventDefault();
        const touch = e.touches[0];
        let currentX = touch.clientX;
        let currentY = touch.clientY;
        offsetX = currentX - startX;
        offsetY = currentY - startY;
        // Dapatkan batas maksimum
        const {
          maxX,
          maxY
        } = getMaxOffsets();
        // Batasi offset agar tidak melebihi batas
        offsetX = Math.max(0, Math.min(offsetX, maxX));
        offsetY = Math.max(0, Math.min(offsetY, maxY));
        hamburger.style.transform = `translate(${offsetX}px, ${offsetY}px)`;
      }
    }, {
      passive: false
    });

    hamburger.addEventListener('touchend', () => {
      isDragging = false;
    });

    // Untuk mouse (desktop)
    hamburger.addEventListener('mousedown', (e) => {
      isDragging = true;
      startX = e.clientX - offsetX;
      startY = e.clientY - offsetY;
    });

    document.addEventListener('mousemove', (e) => {
      if (isDragging) {
        let currentX = e.clientX;
        let currentY = e.clientY;
        offsetX = currentX - startX;
        offsetY = currentY - startY;
        const {
          maxX,
          maxY
        } = getMaxOffsets();
        offsetX = Math.max(0, Math.min(offsetX, maxX));
        offsetY = Math.max(0, Math.min(offsetY, maxY));
        hamburger.style.transform = `translate(${offsetX}px, ${offsetY}px)`;
      }
    });

    document.addEventListener('mouseup', () => {
      isDragging = false;
    });
  });