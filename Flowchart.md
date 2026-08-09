# Jidanao LMS — System Flowchart

This document describes the flow of the Jidanao LMS system using Mermaid flowcharts. Each chart reflects the actual logic implemented in the codebase.

---

## 1. Main System Flow (Overview)

```mermaid
flowchart TD
    A([Start / Visit index.php]) --> B{Already logged in?}
    B -- No --> C[View Home Page]
    B -- Yes --> D[Check Session Role]
    
    C --> E{Action?}
    E -- Register --> F[Register.php]
    E -- Login --> G[login.php]
    E -- Submit Contact Form --> H[Save Contact Message]
    E -- Explore Modules / Courses --> C
    
    D -- Student (0) --> I[index.php]
    D -- Admin (1) --> J[Admin Dashboard]
    D -- Teacher (2) --> K[Teacher Portal]
    
    G --> L{Valid Credentials?}
    L -- No --> G
    L -- Yes --> D
    
    H --> M[contact_messages table]
    M --> C
```

---

## 2. Registration Flow (Register.php)

```mermaid
flowchart TD
    A([Open Register.php]) --> B[Fill in user details]
    B --> C{Create password == Confirm password?}
    C -- No --> D[Show alert: Password does not match]
    D --> B
    C -- Yes --> E[Hash password with password_hash]
    E --> F[Insert into users table]
    F --> G{Insert successful?}
    G -- Yes --> H[Redirect to login.php]
    G -- No --> I[Show: Not inserted to database]
```

---

## 3. Login / Authentication Flow (login.php)

```mermaid
flowchart TD
    A([Open login.php]) --> B[Enter email and password]
    B --> C[Query users table by email]
    C --> D{User found?}
    D -- No --> E[Invalid Email or Password]
    E --> B
    D -- Yes --> F{password_verify?}
    F -- No --> G{Plaintext legacy match?}
    G -- Yes --> H[Upgrade password to hash]
    H --> F
    G -- No --> E
    F -- Yes --> I[Set session: email, usertype, user_id]
    I --> J{usertype?}
    J -- Student (0) --> K[Redirect to index.php]
    J -- Admin (1) --> L[Redirect to Admin-folder/Dashboard.php]
    J -- Teacher (2) --> M[Redirect to Teacher-folder/teacher.php]
```

---

## 4. Admin Dashboard Flow (Dashboard.php)

```mermaid
flowchart TD
    A([Open Dashboard.php]) --> B{Is usertype Admin (1)?}
    B -- No --> C[Redirect to login.php]
    B -- Yes --> D[Ensure LMS tables exist]
    D --> E{POST action?}
    E -- create_course --> F[Validate code & title]
    F --> G{Valid?}
    G -- No --> H[Flash: error]
    G -- Yes --> I[Insert course]
    I --> J[Log activity]
    J --> K[Insert notification: New course available]
    K --> L[Flash: success]
    E -- archive_course --> M[Toggle course status]
    M --> J
    E -- enroll_student --> N[Insert enrollment]
    E -- update_role --> O[Update UserType]
    E -- post_notification --> P[Insert notification]
    L --> Q[Load stats, courses, users, activities]
    H --> Q
    Q --> R([Render Admin Dashboard])
```

---

## 5. User Management Flow (users.php)

```mermaid
flowchart TD
    A([Open users.php]) --> B{Is usertype Admin (1)?}
    B -- No --> C[Redirect to login.php]
    B -- Yes --> D[Ensure student_progress table]
    D --> E{POST action?}
    E -- create --> F[Validate fields & password length]
    F --> G{Valid?}
    G -- No --> H[Flash: error]
    G -- Yes --> I[Hash password & insert user]
    I --> J[Flash: success]
    E -- update --> K[Validate, cannot edit self]
    K --> L[Update user]
    E -- delete --> M[Delete user]
    E -- progress --> N[Validate progress details]
    N --> O[Insert student_progress]
    E -- delete_progress --> P[Delete progress record]
    J --> Q([Load users, students, progress & render])
    H --> Q
    L --> Q
    M --> Q
    O --> Q
    P --> Q
```

---

## 6. Notifications Flow (notifications.php)

```mermaid
flowchart TD
    A([Open notifications.php]) --> B{Is usertype Admin (1)?}
    B -- No --> C[Redirect to login.php]
    B -- Yes --> D{POST action?}
    D -- post_notification --> E[Validate title, message, audience]
    E --> F{Valid?}
    F -- No --> G[Flash: error]
    F -- Yes --> H[Insert notification]
    H --> I[Flash: success]
    D -- delete --> J[Delete notification by id]
    D -- clear_all --> K[Delete all notifications]
    I --> L([Count & list notifications, render])
    G --> L
    J --> L
    K --> L
```

---

## 7. Messages Flow (messages.php + index.php contact form)

```mermaid
flowchart TD
    subgraph Public ["Visitor (index.php)"]
        A[Fill contact form] --> B{Name, valid email, message?}
        B -- No --> C[Show validation error]
        B -- Yes --> D[Insert into contact_messages]
        D --> E[Show success message]
    end

    subgraph Admin ["Administrator (messages.php)"]
        F[Open messages.php] --> G{Is usertype Admin (1)?}
        G -- No --> H[Redirect to login.php]
        G -- Yes --> I{POST action?}
        I -- toggle_read --> J[Toggle is_read status]
        I -- delete --> K[Delete contact message]
        J --> L([Count total/unread, list messages, render])
        K --> L
    end

    E --> F
```

---

## 8. Activity Logs Flow (activity.php)

```mermaid
flowchart TD
    A([Open activity.php]) --> B{Is usertype Admin (1)?}
    B -- No --> C[Redirect to login.php]
    B -- Yes --> D{POST action?}
    D -- delete --> E[Delete log entry by id]
    D -- clear_all --> F[Delete all activity logs]
    E --> G([Count total/today, list activity, render])
    F --> G
```

---

## 9. Student Account Flow (account.php)

```mermaid
flowchart TD
    A([Open account.php]) --> B{Logged in as Student (0)?}
    B -- No --> C[Redirect to login.php]
    B -- Yes --> D[Fetch student profile from users]
    D --> E{Profile exists?}
    E -- No --> F[Destroy session & redirect to login]
    E -- Yes --> G[Fetch enrolled courses]
    G --> H[Fetch student progress records]
    H --> I[Calculate average progress & completed count]
    I --> J([Render student account dashboard])
```

---

## 10. Logout Flow (logout.php)

```mermaid
flowchart TD
    A([Click Logout]) --> B[Start session]
    B --> C[Destroy session]
    C --> D[Redirect to Form-folder/login.php]
    D --> E([End])
```

---

## 11. Combined System Flowchart (All Actors)

```mermaid
flowchart LR
    subgraph Visitor
        V1[Browse Home] --> V2[Contact Form]
        V2 -->|Save| DB[(contact_messages)]
        V1 --> V3[Register]
        V3 -->|Insert| DB[(users)]
        V1 --> V4[Login]
    end

    subgraph Auth
        V4 --> P{Verify Credentials}
        P -->|Student| S
        P -->|Admin| A
        P -->|Teacher| T
    end

    subgraph StudentArea
        S[Student Account] --> S1[View Courses]
        S1 --> E[(courses)]
        S --> S2[View Progress]
        S2 --> P1[(student_progress)]
    end

    subgraph AdminArea
        A[Admin Dashboard] --> A1[Manage Courses]
        A1 --> C[(courses)]
        A --> A2[Enroll Students]
        A2 --> EN[(enrollments)]
        A --> A3[Manage Users]
        A3 --> C
        A --> A4[Post Notifications]
        A4 --> N[(notifications)]
        A --> A5[Read Messages]
        A5 --> M[(contact_messages)]
        A --> A6[View Activity Logs]
        A6 --> L[(activity_logs)]
        A --> A7[Record Progress]
        A7 --> P1
    end

    subgraph Logout
        S --> LO[Logout]
        A --> LO
        LO -->|Destroy session| LG[Back to Login]
    end
```

---

> **Legend:** Rounded rectangles represent pages/processes, diamonds represent decision points, and cylinders represent database tables. The flowcharts are written in Mermaid, so they render natively in GitHub/GitLab and any Markdown viewer that supports Mermaid.
