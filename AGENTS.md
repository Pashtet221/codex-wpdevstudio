# Правила
- Используй scripts/wp
- Не меняй Core, WooCommerce, Parent Theme.
- Перед изменением всегда читай объект.
- После изменения проверяй результат.
- Для скриншотов внешних сайтов используй `scripts/wp capture`, а не сохраняй тяжёлые PNG вручную.
- Для кейсов предпочитай WebP, ширину до 1600 px, осмысленные filename/title/alt.
- Если нужен отдельный блок страницы, используй `--selector`; для мобильного вида — `--mobile`.
- После `capture` используй возвращённый `gutenberg_block` или `media.id`/`media.url` при создании и обновлении контента.

- Не проси пользователя вручную устанавливать Playwright/Sharp/Chromium: `scripts/wp capture` сам запускает `scripts/bootstrap-capture.sh` при необходимости.
