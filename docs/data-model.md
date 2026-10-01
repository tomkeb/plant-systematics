# What the website stores

This is the list of the kinds of records the website keeps in its database, and what each one holds.
Every record also gets an automatic number (ID). Fields marked "Czech + English" are kept in both languages;
where the English text is empty, the Czech one is shown.

Contents: Static pages, News, Theses, Photo categories, Photos, Users.

## Static pages

The fixed pages of the site (home, people, research, courses, laboratory, ...). Each page has its own web address and is written as formatted text.

| Field | What it holds | Required | Note |
|---|---|---|---|
| Slug | short text | yes | URL id, e.g. home, people, research, courses |
| Name | short text | yes | Czech + English |
| Text | formatted text |  | Czech + English |
| Author | link to a user |  | filled in automatically: the user who created it |
| Date | date | yes |  |

## News

News items, shown newest first, each with its own detail page.

| Field | What it holds | Required | Note |
|---|---|---|---|
| Name | short text | yes | Czech + English |
| Text | formatted text |  | Czech + English |
| Author | link to a user |  | filled in automatically: the user who created it |
| Date | date | yes |  |

## Theses

Offered bachelor and master thesis topics, shown on the site grouped by supervisor. Old topics can be archived (hidden) instead of deleted.

| Field | What it holds | Required | Note |
|---|---|---|---|
| Name | short text | yes | Czech + English |
| Description | formatted text |  | Czech + English |
| Category | one of: Bachelor, Master, Both | yes |  |
| Supervisor | link to a user |  |  |
| Field trip share | whole number from 0 to 100 |  |  |
| Visible | yes / no |  | untick to archive; ticked by default |

## Photo categories

The categories of the photo gallery. A photo can belong to several categories.

| Field | What it holds | Required | Note |
|---|---|---|---|
| Name | short text | yes | Czech + English |

## Photos

Gallery photos. Every upload is stored in three sizes (thumbnail, medium, original) and can later be replaced by a new file.

| Field | What it holds | Required | Note |
|---|---|---|---|
| Filename | short text | yes | The image file; set by the upload (lowercase, no diacritics). |
| Name | short text |  | Czech + English |
| Description | formatted text |  | Czech + English |
| Author | short text |  |  |
| Categories | links to several photo categories |  |  |
| Date | date | yes |  |
| Visible in gallery | yes / no |  |  |
| Last edit by | link to a user |  | filled in automatically: the user who saved it last |

## Users

The people who can log in to the administration. They can also be chosen as thesis supervisors.

| Field | What it holds | Required | Note |
|---|---|---|---|
| Name | short text | yes |  |
| Surname | short text | yes |  |
| Email | e-mail address | yes | login |
| Password | password (stored encrypted, never shown) |  | leave empty to keep the current one |
| Registered | date | yes |  |
| Admin rights | yes / no |  |  |

## Adding something new (for example publications)

Another kind of record can be added in the same way. To describe it, please send, for each field:
its name, what it holds (text, number, date, yes/no, a choice from a list, a link to another record, a file),
whether it is required, and whether it needs a Czech and an English version. For example:

| Field | What it holds | Required | Note |
|---|---|---|---|
| Title | short text | yes | Czech + English |
| Authors | short text | yes | e.g. "Novák J., Adler E." |
| Year | whole number | yes | |
| Journal | short text | | |
| Link (DOI) | short text | | |
| PDF file | file | | uploaded like a photo |
| Visible | yes / no | | ticked by default |

Please also say how the records should appear on the site (for example: a list sorted by year, grouped by year,
or one page per record).
