Готовые запросы Codex:

• Найди страницу "Контакты".
• Покажи её ACF.
• Измени hero_title.
• Создай страницу.
• Покажи записи типа wp-plugins.
• Создай запись типа wp-plugins.
• Отредактируй запись типа wp-plugins.
• Просканируй ссылки.
• Замени ссылки.
• Покажи аудит изменений.

Команды вручную:

```bash
# Получить все записи типа wp-plugins
scripts/wp wp-plugins

# Создать запись типа wp-plugins
scripts/wp create-wp-plugin examples/create-wp-plugin.json

# Изменить запись типа wp-plugins, где 123 — ID записи
scripts/wp update-wp-plugin 123 examples/update-wp-plugin.json
```

## Услуги (`service`)

Дополнение `wordpress-plugin/codex-bridge-service-access` добавляет тип записи
`service` в whitelist Codex Bridge. Права на каждую операцию по-прежнему
проверяются WordPress через capabilities этого типа записи.

```bash
# Просмотреть услуги
scripts/wp services

# Создать услугу (payload должен содержать "post_type": "service")
scripts/wp create-service payload.json

# Изменить услугу
scripts/wp update-service POST_ID payload.json

# Удалить услугу
scripts/wp delete-service POST_ID
```

Важно: WordPress-плагин Codex Bridge на самом сайте должен разрешать post type `wp-plugins` в своём списке допустимых типов записей. Этот архив содержит клиентские команды, а не PHP-код серверного плагина.

## Rank Math SEO meta

Расширение `wordpress-plugin/codex-bridge-rank-math` добавляет отдельный endpoint для
трёх разрешённых полей Rank Math. Оно использует тот же whitelist типов записей,
который возвращает Codex Bridge, и стандартные WordPress capabilities объекта.

```bash
scripts/wp seo POST_ID
scripts/wp update-seo POST_ID payload.json
```

Пример payload (передаются только изменяемые поля):

```json
{
  "rank_math_title": "Проверочный SEO title",
  "rank_math_description": "Проверочное SEO description",
  "rank_math_focus_keyword": "проверочный запрос"
}
```

Пустая строка или `null` удаляет конкретное meta-поле. Произвольные meta keys
endpoint не принимает.

## Media / screenshots

```bash
scripts/wp media-upload FILE.webp [--post-id=ID] [--alt=TEXT] [--title=TEXT] [--caption=TEXT] [--description=TEXT] [--set-featured]
scripts/wp media-sideload payload.json
scripts/wp thumbnail POST_ID ATTACHMENT_ID
scripts/wp capture URL NAME [--selector=CSS] [--mobile] [--mode=page|viewport] [--post-id=ID] [--set-featured]
```

`capture` performs browser capture + WebP optimization + WordPress Media upload and returns a ready `gutenberg_block`.
