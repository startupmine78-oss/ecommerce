const Toast = {
    container: null,
    init() {
        this.container = document.getElementById('toast-container');
        if (!this.container) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            document.body.appendChild(this.container);
        }
    },
    show(msg, type = 'success', duration = 3000) {
        const icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️', cart: '🛒', heart: '❤️' };
        const colors = {
            success: 'linear-gradient(135deg,#10B981,#059669)',
            error: 'linear-gradient(135deg,#EF4444,#DC2626)',
            info: 'linear-gradient(135deg,#3B82F6,#2563EB)',
            warning: 'linear-gradient(135deg,#F59E0B,#D97706)',
            cart: 'linear-gradient(135deg,#FF6B35,#E85A20)',
            heart: 'linear-gradient(135deg,#EC4899,#DB2777)'
        };
        const toast = document.createElement('div');
        toast.className = 'toast-item';
        toast.innerHTML = `<span class="toast-icon">${icons[type]||icons.success}</span><span>${msg}</span><button class="toast-close" onclick="this.parentElement.remove()">✕</button>`;
        toast.style.cssText = `background:${colors[type]||colors.success};color:#fff;padding:14px 18px;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,0.2);display:flex;align-items:center;gap:10px;font-size:0.92rem;font-weight:600;animation:slideInRight 0.4s ease;max-width:340px;`;
        this.container.appendChild(toast);
        setTimeout(() => { toast.style.animation = 'slideOutRight 0.4s ease forwards'; setTimeout(() => toast.remove(), 400); }, duration);
    }
};

// LIVE SEARCH 
const LiveSearch = {
    input: null,
    dropdown: null,
    timer: null,
    init() {
        this.input = document.querySelector('.search-bar input');
        if (!this.input) return;
        this.dropdown = document.createElement('div');
        this.dropdown.id = 'search-dropdown';
        this.input.parentElement.style.position = 'relative';
        this.input.parentElement.appendChild(this.dropdown);

        this.input.addEventListener('input', () => {
            clearTimeout(this.timer);
            const q = this.input.value.trim();
            if (q.length < 2) { this.hide(); return; }
            this.showSkeleton();
            this.timer = setTimeout(() => this.fetch(q), 300);
        });

        document.addEventListener('click', (e) => {
            if (!this.input.parentElement.contains(e.target)) this.hide();
        });

        this.input.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') this.hide();
        });
    },
    showSkeleton() {
        this.dropdown.innerHTML = Array(4).fill(0).map(() => `
            <div class="search-item skeleton-item">
                <div class="skeleton-img"></div>
                <div style="flex:1"><div class="skeleton-line" style="width:70%;margin-bottom:6px;"></div><div class="skeleton-line" style="width:40%;"></div></div>
            </div>`).join('');
        this.dropdown.style.display = 'block';
    },
    fetch(q) {
        fetch(`ajax/search.php?q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => this.render(data, q));
    },
    render(results, q) {
        if (!results.length) {
            this.dropdown.innerHTML = `<div style="padding:20px;text-align:center;color:#999;">😕 "${q}" олдсонгүй</div>`;
            this.dropdown.style.display = 'block';
            return;
        }
        const highlight = (text) => text.replace(new RegExp(`(${q})`, 'gi'), '<mark>$1</mark>');
        this.dropdown.innerHTML = `
            <div class="search-header">🔍 "${q}" хайлтын үр дүн (${results.length})</div>
            ${results.map(p => `
            <a href="${p.url}" class="search-item">
                <img src="${p.image}" onerror="this.src='https://via.placeholder.com/50'" alt="">
                <div style="flex:1">
                    <div class="search-name">${highlight(p.name)}</div>
                    <div class="search-meta">${p.category} · ⭐${p.rating}</div>
                </div>
                <div class="search-price">${p.price}</div>
            </a>`).join('')}
            <a href="products.php?q=${encodeURIComponent(q)}" class="search-all">Бүгдийг харах →</a>`;
        this.dropdown.style.display = 'block';
    },
    hide() { this.dropdown.style.display = 'none'; }
};

//WISHLIST 
const Wishlist = {
    items: JSON.parse(localStorage.getItem('wishlist') || '[]'),
    toggle(productId, btn) {
        fetch('ajax/wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'product_id=' + productId
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const added = data.action === 'added';
                if (btn) {
                    btn.classList.toggle('active', added);
                    btn.innerHTML = added ? '❤️' : '🤍';
                    btn.title = added ? 'Wishlist-аас хасах' : 'Wishlist-д нэмэх';
                }
                // Update all buttons for same product
                document.querySelectorAll(`[data-wishlist="${productId}"]`).forEach(b => {
                    b.classList.toggle('active', added);
                    b.innerHTML = added ? '❤️' : '🤍';
                });
                Toast.show(added ? 'Дуртайд нэмэгдлээ!' : 'Дуртайнаас хасагдлаа', added ? 'heart' : 'info');
                // Update count badge
                const badge = document.getElementById('wishlist-count');
                if (badge) badge.textContent = data.count;
            }
        });
    },
    isInWishlist(id) { return this.items.includes(id); }
};

//QUICK VIEW MODAL 
const QuickView = {
    modal: null,
    init() {
        this.modal = document.getElementById('quick-view-modal');
    },
    open(productId) {
        if (!this.modal) return;
        this.modal.classList.add('open');
        document.body.style.overflow = 'hidden';
        const body = this.modal.querySelector('.qv-body');
        body.innerHTML = `<div class="qv-skeleton">${Array(6).fill('<div class="skeleton-line" style="margin-bottom:12px;"></div>').join('')}</div>`;

        fetch(`ajax/quick_view.php?id=${productId}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                const stars = Array(5).fill(0).map((_, i) =>
                    i < Math.round(data.rating) ? '⭐' : '☆').join('');
                body.innerHTML = `
                <div class="qv-layout">
                    <div class="qv-img-wrap">
                        <img src="${data.image}" onerror="this.src='https://via.placeholder.com/400x350'" alt="${data.name}">
                        ${data.badge ? `<span class="product-badge">${data.badge}</span>` : ''}
                        ${data.discount ? `<span class="qv-discount-badge">-${data.discount}%</span>` : ''}
                    </div>
                    <div class="qv-info">
                        <div class="product-category-tag">${data.category}</div>
                        <h2 class="qv-title">${data.name}</h2>
                        <div class="product-rating" style="margin-bottom:12px;">
                            <span style="font-size:1.1rem;">${stars}</span>
                            <span style="font-weight:700;">${data.rating}</span>
                            <span style="color:var(--text-light);font-size:0.85rem;">(${data.reviews_count} үнэлгээ)</span>
                        </div>
                        <div class="qv-price">
                            <span class="price">${data.price}</span>
                            ${data.original_price ? `<span class="price-original">${data.original_price}</span>` : ''}
                        </div>
                        <p class="qv-desc">${data.description}</p>
                        <div class="qv-stock ${data.stock > 0 ? 'in' : 'out'}">
                            ${data.stock > 0 ? `✅ Нөөцтэй (${data.stock} ш)` : '❌ Нөөцгүй'}
                        </div>
                        ${data.stock > 0 ? `
                        <div class="qv-actions">
                            <div class="qty-control">
                                <button class="qty-btn" onclick="document.getElementById('qv-qty').value=Math.max(1,+document.getElementById('qv-qty').value-1)">−</button>
                                <input type="number" id="qv-qty" value="1" min="1" max="${data.stock}" style="width:50px;text-align:center;border:2px solid var(--border);border-radius:8px;padding:6px;font-size:1rem;font-weight:700;">
                                <button class="qty-btn" onclick="document.getElementById('qv-qty').value=Math.min(${data.stock},+document.getElementById('qv-qty').value+1)">+</button>
                            </div>
                            <button class="btn-primary" style="flex:1;" onclick="addToCartQV(${data.id})">
                                🛒 Сагсанд нэмэх
                            </button>
                        </div>
                        <a href="${data.url}" class="qv-detail-link">Дэлгэрэнгүй харах →</a>
                        ` : `<a href="${data.url}" class="btn-primary" style="margin-top:16px;display:inline-flex;">Дэлгэрэнгүй харах</a>`}
                    </div>
                </div>`;
            });
    },
    close() {
        if (!this.modal) return;
        this.modal.classList.remove('open');
        document.body.style.overflow = '';
    }
};

function addToCartQV(id) {
    const qty = document.getElementById('qv-qty')?.value || 1;
    fetch('ajax/add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `product_id=${id}&quantity=${qty}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.count);
            Toast.show(`Сагсанд нэмэгдлээ! (${qty} ш)`, 'cart');
            QuickView.close();
        }
    });
}

//CART SIDEBAR DRAWER 
const CartDrawer = {
    drawer: null,
    init() {
        this.drawer = document.getElementById('cart-drawer');
    },
    open() {
        if (!this.drawer) return;
        this.drawer.classList.add('open');
        document.getElementById('cart-overlay').classList.add('open');
        document.body.style.overflow = 'hidden';
        this.load();
    },
    close() {
        if (!this.drawer) return;
        this.drawer.classList.remove('open');
        document.getElementById('cart-overlay').classList.remove('open');
        document.body.style.overflow = '';
    },
    load() {
        const body = document.getElementById('cart-drawer-body');
        body.innerHTML = `<div style="padding:40px;text-align:center;color:#999;">${Array(3).fill('<div class="skeleton-line" style="margin-bottom:12px;"></div>').join('')}</div>`;
        fetch('ajax/get_cart.php')
            .then(r => r.json())
            .then(data => this.render(data));
    },
    render(data) {
        const body = document.getElementById('cart-drawer-body');
        const footer = document.getElementById('cart-drawer-footer');

        if (!data.items.length) {
            body.innerHTML = `<div style="padding:60px 20px;text-align:center;">
                <div style="font-size:3rem;margin-bottom:12px;">🛒</div>
                <p style="color:#999;font-weight:600;">Сагс хоосон байна</p>
                <a href="products.php" onclick="CartDrawer.close()" class="btn-primary" style="margin-top:16px;display:inline-flex;font-size:0.9rem;padding:10px 20px;">Дэлгүүр хийх</a>
            </div>`;
            footer.innerHTML = '';
            return;
        }

        body.innerHTML = data.items.map(item => `
            <div class="cd-item" id="cd-item-${item.cart_id}">
                <img src="${item.image}" onerror="this.src='https://via.placeholder.com/60'" alt="">
                <div class="cd-item-info">
                    <div class="cd-item-name">${item.name}</div>
                    <div class="cd-item-price">${item.price_formatted} × ${item.quantity} = <strong>${item.subtotal}</strong></div>
                    <div style="display:flex;align-items:center;gap:8px;margin-top:6px;">
                        <button class="qty-btn" style="width:26px;height:26px;font-size:0.85rem;" onclick="cartDrawerQty(${item.cart_id}, ${item.product_id}, ${item.quantity - 1})">−</button>
                        <span style="font-weight:700;min-width:20px;text-align:center;">${item.quantity}</span>
                        <button class="qty-btn" style="width:26px;height:26px;font-size:0.85rem;" onclick="cartDrawerQty(${item.cart_id}, ${item.product_id}, ${item.quantity + 1})">+</button>
                    </div>
                </div>
                <button class="cd-remove" onclick="cartDrawerRemove(${item.cart_id})" title="Устгах">✕</button>
            </div>`).join('');

        footer.innerHTML = `
            <div class="cd-totals">
                <div class="cd-row"><span>Нийт:</span><span>${data.subtotal}</span></div>
                <div class="cd-row" style="font-size:0.82rem;color:#999;"><span>Хүргэлт:</span><span>${data.shipping}</span></div>
                <div class="cd-row cd-total"><span>Нийт дүн:</span><span style="color:var(--primary);">${data.total}</span></div>
            </div>
            <a href="checkout.php" class="btn-primary" style="display:flex;justify-content:center;margin-top:14px;" onclick="CartDrawer.close()">
                🔒 Захиалга хийх — ${data.total}
            </a>
            <a href="cart.php" style="display:block;text-align:center;margin-top:10px;font-size:0.85rem;color:#999;" onclick="CartDrawer.close()">Сагс харах →</a>`;
    }
};

function cartDrawerRemove(cartId) {
    const el = document.getElementById(`cd-item-${cartId}`);
    if (el) { el.style.animation = 'slideOutRight 0.3s ease forwards'; setTimeout(() => el.remove(), 300); }
    fetch('ajax/remove_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'cart_id=' + cartId
    })
    .then(r => r.json())
    .then(data => {
        updateCartCount(data.count);
        setTimeout(() => CartDrawer.load(), 400);
    });
}

function cartDrawerQty(cartId, productId, newQty) {
    if (newQty < 1) { cartDrawerRemove(cartId); return; }
    fetch('ajax/add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `product_id=${productId}&quantity=1&cart_id=${cartId}&set_qty=${newQty}`
    })
    .then(() => CartDrawer.load());
}

//ADD TO CART (global)
function addToCart(productId, btn) {
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner"></span> Нэмж байна...';
    }
    fetch('ajax/add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + productId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.count);
            Toast.show('Сагсанд нэмэгдлээ! 🛒', 'cart');
            if (btn) {
                btn.innerHTML = '✅ Нэмэгдлээ!';
                btn.style.background = 'var(--success)';
                setTimeout(() => {
                    btn.disabled = false;
                    btn.innerHTML = '🛒 Сагсанд нэмэх';
                    btn.style.background = '';
                }, 1800);
            }
        }
    });
}

function updateCartCount(count) {
    document.querySelectorAll('#cartCount, .cart-count').forEach(el => {
        el.textContent = count;
        el.style.transform = 'scale(1.4)';
        setTimeout(() => el.style.transform = '', 300);
    });
}

function showProductSkeletons(count = 8) {
    const grid = document.querySelector('.products-grid');
    if (!grid) return;
    grid.innerHTML = Array(count).fill(`
        <div class="product-skeleton">
            <div class="skeleton-img-full"></div>
            <div style="padding:14px;">
                <div class="skeleton-line" style="width:40%;margin-bottom:8px;"></div>
                <div class="skeleton-line" style="width:90%;margin-bottom:6px;"></div>
                <div class="skeleton-line" style="width:70%;margin-bottom:12px;"></div>
                <div class="skeleton-line" style="width:50%;"></div>
            </div>
        </div>`).join('');
}

const BackToTop = {
    btn: null,
    init() {
        this.btn = document.getElementById('back-to-top');
        if (!this.btn) return;
        window.addEventListener('scroll', () => {
            this.btn.classList.toggle('show', window.scrollY > 400);
        });
        this.btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    }
};

const ScrollAnim = {
    init() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('anim-in');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });

        document.querySelectorAll('.product-card-wrap, .category-card, .promo-card').forEach(el => {
            el.classList.add('anim-ready');
            observer.observe(el);
        });
    }
};

const CountdownTimer = {
    init() {
        const el = document.getElementById('flash-countdown');
        if (!el) return;
        let end = new Date();
        end.setHours(end.getHours() + 4, end.getMinutes() + 32, end.getSeconds() + 17);
        setInterval(() => {
            const diff = end - new Date();
            if (diff <= 0) { el.textContent = '00:00:00'; return; }
            const h = String(Math.floor(diff/3600000)).padStart(2,'0');
            const m = String(Math.floor((diff%3600000)/60000)).padStart(2,'0');
            const s = String(Math.floor((diff%60000)/1000)).padStart(2,'0');
            el.textContent = `${h}:${m}:${s}`;
        }, 1000);
    }
};

const StickyBar = {
    init() {
        const bar = document.getElementById('sticky-product-bar');
        const detailInfo = document.querySelector('.product-detail-info');
        if (!bar || !detailInfo) return;
        const observer = new IntersectionObserver((entries) => {
            bar.classList.toggle('show', !entries[0].isIntersecting);
        }, { threshold: 0 });
        observer.observe(detailInfo);
    }
};

const Tooltip = {
    init() {
        document.querySelectorAll('[data-tooltip]').forEach(el => {
            el.addEventListener('mouseenter', (e) => {
                const tip = document.createElement('div');
                tip.className = 'tooltip-popup';
                tip.textContent = el.dataset.tooltip;
                document.body.appendChild(tip);
                const rect = el.getBoundingClientRect();
                tip.style.cssText = `position:fixed;top:${rect.top-36}px;left:${rect.left+rect.width/2-tip.offsetWidth/2}px;`;
                el._tip = tip;
            });
            el.addEventListener('mouseleave', () => { el._tip?.remove(); });
        });
    }
};

//INITIALIZE ALL
document.addEventListener('DOMContentLoaded', () => {
    Toast.init();
    LiveSearch.init();
    QuickView.init();
    CartDrawer.init();
    BackToTop.init();
    ScrollAnim.init();
    CountdownTimer.init();
    StickyBar.init();
    Tooltip.init();

    const cartBtn = document.querySelector('.cart-btn');
    if (cartBtn && window.innerWidth > 768) {
        cartBtn.addEventListener('click', (e) => {
            e.preventDefault();
            CartDrawer.open();
        });
    }


    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            QuickView.close();
            CartDrawer.close();
        }
    });
});