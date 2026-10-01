# Database Schema

The application schema contains four tables. Laravel also creates its internal `migrations` table to track applied migrations; it is not an application entity.

| Table | Purpose | Indexes and relationships |
| --- | --- | --- |
| `users` | Login accounts and roles | Unique email; indexed role |
| `tasks` | Work items, status, priority, assignee, creator, and due date | Indexed status, priority, assignee, creator, and due date; assignee deletion sets the reference to `NULL`; creator deletion is restricted |
| `task_attachments` | Private attachment metadata | Indexed task ID; task deletion cascades |
| `task_comments` | Comment records | Composite task/user index and user index; task/user deletion cascades |

[`database/schema.sql`](../database/schema.sql) contains the MySQL DDL reference. [`database/task_management_dump.sql`](../database/task_management_dump.sql) contains the schema plus five users, fifteen tasks, and ten comments for import into a new empty database.

Laravel migrations under [`database/migrations/`](../database/migrations/) are the normal schema source of truth. The repeatable sample seeder is [`database/seeders/DatabaseSeeder.php`](../database/seeders/DatabaseSeeder.php). User passwords are stored as bcrypt hashes by the model's hashed cast; plaintext demo credentials are for local assessment use only.