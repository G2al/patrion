# Patrion API v1

Il frontend usa le API Laravel sotto `/api/v1` con un Bearer token Sanctum.

## Autenticazione

- `POST /auth/login` con `email` e `password`; restituisce `data.token` e `data.user`.
- `GET /auth/me` richiede `Authorization: Bearer <token>`.
- `POST /auth/logout` invalida il token corrente.

## Endpoint autenticati

- `GET /dashboard`
- `GET /clients/dashboard?months=3|6|12&neglected_limit=10`
- `GET /contacts?search=&status=&page=&per_page=`
- `GET /contacts/{contact}`
- `GET /companies?search=&page=&per_page=`
- `GET /companies/{company}`
- `GET /appointments?from=&to=&page=&per_page=`
- `GET /appointments/{appointment}`
- `GET /activities?open=1&overdue=1&page=&per_page=`
- `GET /practices?status=&page=&per_page=`
- `GET /goals`
- `GET /documents?page=&per_page=`
- `GET /documents/{document}`
- `GET /practices/{practice}`
- `GET /goals/{goal}`
- `GET /search?q=`
- `GET /lookups`
- `GET /notifications`
- `PATCH /notifications/{id}/read`
- `PATCH /notifications/read-all`
- `GET /settings`
- `PATCH /settings`
- `PATCH /auth/profile`
- `PATCH /auth/password`
- `GET /documents/{document}/download`
- `GET /documents/{document}/preview`

Le query paginated restituiscono la struttura standard Laravel (`data`, `links`, `meta`).

## Scrittura

Sono disponibili POST/PATCH/DELETE per contatti, aziende, appuntamenti, attività, pratiche, obiettivi e documenti. Gli upload documenti usano `multipart/form-data` con il campo `file`. Le note usano `POST /contacts/{contact}/notes`, `PATCH/DELETE /notes/{note}`. I collegamenti azienda-contatto usano `POST /companies/{company}/contacts` e `DELETE /companies/{company}/contacts/{contact}`.
