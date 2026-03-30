# Banking API — Take-Home Assignment

## Overview

This project is a **work-in-progress implementation** of a minimal banking-like HTTP API.

The goal is to model simple account operations (deposit, withdraw, transfer) while keeping the solution:

* simple
* correct
* easy to reason about
* aligned with real-world backend design principles

At the current stage, the project focuses on **architecture, contracts, and infrastructure setup**, with the core business logic still being implemented.

---

## Architecture

The application follows a clean and minimal layered architecture:

* **Controllers**

  * Handle HTTP requests and responses
  * Keep logic thin and focused on transport concerns

* **Service Layer**

  * Encapsulates business logic
  * Coordinates operations between controllers and persistence

* **Repository Layer**

  * Abstracts data access
  * Responsible for interacting with Redis

* **Redis (planned)**

  * Will be used as the backing store for account balances

---

## Why Redis?

This problem models **ephemeral state**, not a fully persistent financial system.

For this reason, Redis was chosen because:

* it provides fast, in-memory data access
* it simplifies state management across requests
* it avoids unnecessary complexity from relational modeling
* it is well-suited for transient data

In a real production banking system, a relational database and a ledger-based model would be required for durability and auditability.

---

## Planned Data Model

Accounts will be stored in Redis using a single hash:

```text
Key: accounts

Fields:
  100 => 20
  300 => 15
```

* each field represents an account ID
* each value represents the current balance

---

## Running the Project

### Requirements

* Docker
* Docker Compose

### Start the application

```bash
docker compose up --build
```

The API will be available at:

```
http://localhost:8000
```

---

## Intended API Contract

The following endpoints describe the **target behavior** of the system once fully implemented.

### Reset state

```http
POST /reset
```

Response:

* `200 OK`
* empty body

---

### Get balance

```http
GET /balance?account_id=100
```

Responses:

* Account not found:

  * `404`
  * body: `0`

* Account exists:

  * `200`
  * body: `20`

---

### Event operations

```http
POST /event
```

#### Deposit

```json
{
  "type": "deposit",
  "destination": "100",
  "amount": 10
}
```

#### Withdraw

```json
{
  "type": "withdraw",
  "origin": "100",
  "amount": 5
}
```

#### Transfer

```json
{
  "type": "transfer",
  "origin": "100",
  "destination": "300",
  "amount": 15
}
```

---

## Design Decisions

### 1. Minimal Infrastructure

* No MySQL
* No Nginx
* No queues or background workers

The focus is on correctness and clarity, not infrastructure complexity.

---

### 2. Separation of Concerns

* Controllers handle HTTP
* Services handle business rules
* Repositories handle data access

---

### 3. Domain-Oriented Repository

The repository exposes domain-level operations such as:

* deposit
* withdraw
* transfer

Instead of generic CRUD operations.

---

### 4. Service Layer Responsibility

The service layer:

* orchestrates operations
* shapes domain responses
* remains independent from HTTP

---

## Current Status

* [x] Dockerized environment
* [x] Application architecture
* [ ] Redis repository implementation
* [ ] Atomic operations (Lua scripts)
* [ ] End-to-end API behavior
* [ ] Automated tests

---

## Future Improvements (Production Context)

If this were a real system, I would consider:

* relational database as source of truth
* ledger-based transaction model
* audit logging
* idempotency handling
* stronger concurrency guarantees
* authentication and authorization
* observability (metrics, logs, tracing)

---

## Author

João Victor Morais de Mello
