<?php // includes/footer.php ?>
<footer style="
    background: #1A1A2E;
    color: white;
    margin-top: 60px;
    font-family: 'DM Sans', sans-serif;
">
    <!-- Main footer grid -->
    <div style="
        max-width: 1400px;
        margin: 0 auto;
        padding: 50px 24px 40px;
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 40px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    ">
        <!-- Brand column -->
        <div>
            <a href="index.php" style="
                font-family: 'Outfit', sans-serif;
                font-size: 1.8rem;
                font-weight: 900;
                color: white;
                text-decoration: none;
                letter-spacing: -1px;
                display: inline-block;
                margin-bottom: 14px;
            ">Shop<span style="color: #FF6B35;">MN</span></a>

            <p style="
                color: rgba(255,255,255,0.6);
                font-size: 0.9rem;
                line-height: 1.7;
                margin-bottom: 20px;
                max-width: 320px;
            ">Монгол улсын хамгийн том, найдвартай онлайн худалдааны платформ. 10,000+ бүтээгдэхүүн, 500+ брэнд.</p>

            <!-- Social icons -->
            <div style="display: flex; gap: 10px;">
                <a href="#" style="
                    width: 38px; height: 38px;
                    background: rgba(255,255,255,0.1);
                    border-radius: 8px;
                    display: flex; align-items: center; justify-content: center;
                    color: white; text-decoration: none;
                    font-size: 0.95rem;
                    transition: background 0.2s;
                " onmouseover="this.style.background='#FF6B35'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                    <i class="fab fa-facebook-f"></i>
                </a>
                <a href="#" style="
                    width: 38px; height: 38px;
                    background: rgba(255,255,255,0.1);
                    border-radius: 8px;
                    display: flex; align-items: center; justify-content: center;
                    color: white; text-decoration: none;
                    font-size: 0.95rem;
                    transition: background 0.2s;
                " onmouseover="this.style.background='#FF6B35'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="#" style="
                    width: 38px; height: 38px;
                    background: rgba(255,255,255,0.1);
                    border-radius: 8px;
                    display: flex; align-items: center; justify-content: center;
                    color: white; text-decoration: none;
                    font-size: 0.95rem;
                    transition: background 0.2s;
                " onmouseover="this.style.background='#FF6B35'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" style="
                    width: 38px; height: 38px;
                    background: rgba(255,255,255,0.1);
                    border-radius: 8px;
                    display: flex; align-items: center; justify-content: center;
                    color: white; text-decoration: none;
                    font-size: 0.95rem;
                    transition: background 0.2s;
                " onmouseover="this.style.background='#FF6B35'" onmouseout="this.style.background='rgba(255,255,255,0.1)'">
                    <i class="fab fa-youtube"></i>
                </a>
            </div>
        </div>

        <!-- Бидний тухай -->
        <div>
            <h4 style="
                font-family: 'Outfit', sans-serif;
                font-weight: 700;
                font-size: 0.95rem;
                margin-bottom: 18px;
                color: white;
            ">Бидний тухай</h4>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php foreach (['Компанийн тухай', 'Карьер', 'Хэвлэлийн мэдэгдэл', 'Нийгмийн хариуцлага'] as $item): ?>
                <li style="margin-bottom: 12px;">
                    <a href="#" style="
                        color: rgba(255,255,255,0.6);
                        text-decoration: none;
                        font-size: 0.88rem;
                        transition: color 0.2s;
                    " onmouseover="this.style.color='#FF6B35'" onmouseout="this.style.color='rgba(255,255,255,0.6)'"><?= $item ?></a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Тусламж -->
        <div>
            <h4 style="
                font-family: 'Outfit', sans-serif;
                font-weight: 700;
                font-size: 0.95rem;
                margin-bottom: 18px;
                color: white;
            ">Тусламж</h4>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php foreach (['Хүргэлтийн нөхцөл', 'Буцаалтын бодлого', 'Нэвтрэх / Бүртгэл', 'Тусламжийн төв'] as $item): ?>
                <li style="margin-bottom: 12px;">
                    <a href="#" style="
                        color: rgba(255,255,255,0.6);
                        text-decoration: none;
                        font-size: 0.88rem;
                        transition: color 0.2s;
                    " onmouseover="this.style.color='#FF6B35'" onmouseout="this.style.color='rgba(255,255,255,0.6)'"><?= $item ?></a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Холбоо барих -->
        <div>
            <h4 style="
                font-family: 'Outfit', sans-serif;
                font-weight: 700;
                font-size: 0.95rem;
                margin-bottom: 18px;
                color: white;
            ">Холбоо барих</h4>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 12px;">
                    <a href="tel:70001234" style="color: rgba(255,255,255,0.6); text-decoration: none; font-size: 0.88rem; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-phone" style="color: #FF6B35; width: 14px;"></i> 7000-1234
                    </a>
                </li>
                <li style="margin-bottom: 12px;">
                    <a href="mailto:info@shopmn.mn" style="color: rgba(255,255,255,0.6); text-decoration: none; font-size: 0.88rem; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-envelope" style="color: #FF6B35; width: 14px;"></i> info@shopmn.mn
                    </a>
                </li>
                <li style="margin-bottom: 12px;">
                    <span style="color: rgba(255,255,255,0.6); font-size: 0.88rem; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-map-marker-alt" style="color: #FF6B35; width: 14px;"></i> Улаанбаатар, Монгол
                    </span>
                </li>
                <li style="margin-bottom: 12px;">
                    <span style="color: rgba(255,255,255,0.6); font-size: 0.88rem; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-clock" style="color: #FF6B35; width: 14px;"></i> 09:00 – 21:00
                    </span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Footer bottom bar -->
    <div style="
        max-width: 1400px;
        margin: 0 auto;
        padding: 18px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    ">
        <p style="color: rgba(255,255,255,0.45); font-size: 0.82rem; margin: 0;">
            &copy; <?= date('Y') ?> ShopMN. Энэхүү вебсайт нь курсын ажилд зориулагдан бүтээгдсэн болно
        </p>
        <p style="color: rgba(255,255,255,0.45); font-size: 0.82rem; margin: 0;">
            💳 Visa | MasterCard | QPay | SocialPay | MoNPay
        </p>
    </div>
</footer>
</body>
</html>