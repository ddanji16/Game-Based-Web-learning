# Jidanao LMS — Data Model (ER Diagram)

```mermaid
erDiagram
    USERS ||--o{ COURSES : "teaches (teacher_id)"
    USERS ||--o{ ACTIVITY_LOGS : "performs (user_id)"
    USERS ||--o{ STUDENT_PROGRESS : "tracks (student_id)"

    USERS {
        int id PK
        string Firstname
        string Middlename
        string Lastname
        string Email
        string createpassword
        string confirmpassword
        int UserType "0=Student, 1=Admin, 2=Teacher"
    }

    COURSES {
        int id PK
        string course_code UK
        string title
        text description
        int teacher_id FK "references users"
        enum status "active | archived"
        datetime created_at
    }

    NOTIFICATIONS {
        int id PK
        string title
        text message
        enum audience "all | students | teachers"
        datetime created_at
    }

    ACTIVITY_LOGS {
        int id PK
        int user_id FK "references users, nullable"
        string action_text
        datetime created_at
    }

    CONTACT_MESSAGES {
        int id PK
        string name
        string email
        string subject
        text message
        int is_read "0=unread, 1=read"
        datetime created_at
    }

    STUDENT_PROGRESS {
        int id PK
        int student_id FK "references users"
        string activity_title
        int progress_percentage
        decimal score
        enum status "not_started | in_progress | completed"
        datetime updated_at
    }

