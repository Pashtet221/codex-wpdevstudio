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
