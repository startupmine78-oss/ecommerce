</div>
</div>
<script>
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('sidebar');
    if (sidebar && !sidebar.contains(e.target) && !e.target.closest('.topbar-btn')) {
        sidebar.classList.remove('open');
    }
});
document.querySelectorAll('.chart-bar-fill').forEach(bar => {
    const w = bar.getAttribute('data-width') || '0';
    setTimeout(() => bar.style.width = w + '%', 200);
});
document.querySelectorAll('.alert').forEach(a => {
    setTimeout(() => a.style.display = 'none', 4000);
});
</script>
</body>
</html>