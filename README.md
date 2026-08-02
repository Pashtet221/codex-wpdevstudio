# Codex WordPress Cloud v3

1. Загрузите проект в GitHub.
2. Подключите репозиторий к Codex.
3. Заполните config/.env.example значениями сайта.
4. Создайте Environment Variables в Codex с теми же именами.

После этого Codex использует scripts/wp автоматически.

## Поддерживаемые типы записей

К стандартным `page` и `post` добавлена работа с пользовательским типом записи:

```text
wp-plugins
```

Доступны просмотр, создание и редактирование:

```bash
scripts/wp wp-plugins
scripts/wp create-wp-plugin examples/create-wp-plugin.json
scripts/wp update-wp-plugin POST_ID examples/update-wp-plugin.json
```

Серверный WordPress-плагин Codex Bridge также должен иметь `wp-plugins` в whitelist разрешённых post type.

## Скриншоты сайта → WebP → WordPress Media → Gutenberg

В v4.1 команда `capture` стала самонастраиваемой: при первом запуске Cloud автоматически устанавливает Playwright, Sharp и Chromium. Вручную выполнять `npm install` или `npx playwright install chromium` не нужно.

В v4 добавлена команда `capture`. Она открывает внешний сайт через Playwright, делает скриншот всей страницы или отдельного блока, оптимизирует изображение в WebP через Sharp, загружает его в медиатеку WordPress через Codex Bridge и возвращает JSON с `media.id`, `media.url` и готовым `gutenberg_block`.

Один раз установите зависимости в Codex-окружении:

```bash
# Ничего устанавливать вручную не нужно.
# При первом `scripts/wp capture ...` зависимости и Chromium установятся автоматически.
```

Полная страница:

```bash
scripts/wp capture https://example.com example-home --alt="Главная страница Example"
```

Конкретный блок:

```bash
scripts/wp capture https://example.com/catalog catalog \
  --selector=".products" \
  --alt="Каталог товаров Example" \
  --title="Каталог Example"
```

Мобильный viewport:

```bash
scripts/wp capture https://example.com example-mobile --mobile
```

Сразу привязать к записи и назначить миниатюрой:

```bash
scripts/wp capture https://example.com case-cover \
  --post-id=123 \
  --set-featured \
  --alt="Интернет-магазин Example"
```

По умолчанию результат ограничивается шириной 1600 px и сохраняется WebP quality 80. Для длинных страниц можно ограничить высоту:

```bash
scripts/wp capture https://example.com long-page --max-height=1800
```

Для прямой загрузки уже готового файла:

```bash
scripts/wp media-upload ./image.webp --post-id=123 --alt="Описание" --title="Название"
```

Ответ `capture` содержит готовый Gutenberg-блок изображения. Его можно вставлять в поле `content` при `create`/`update` без ручной сборки markup.
