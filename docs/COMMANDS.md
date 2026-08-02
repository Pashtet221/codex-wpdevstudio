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

Важно: WordPress-плагин Codex Bridge на самом сайте должен разрешать post type `wp-plugins` в своём списке допустимых типов записей. Этот архив содержит клиентские команды, а не PHP-код серверного плагина.
