# Banking API — Take-Home Assignment

## Overview

This project is an implementation of a minimal banking-like HTTP API built for a take-home assignment.

The goal is to model simple account operations (deposit, withdraw, transfer) while keeping the solution:

* simple
* correct
* easy to reason about
* aligned with real-world backend design principles

This project includes a complete Docker setup, a Laravel-based architecture, Redis-backed state management, a fully implemented API, and automated feature tests covering the expected behavior.

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

* **Redis**

  * Used as the backing store for account balances

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

## Data Model

Accounts are stored in Redis using a single hash:

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

## API Contract

The following endpoints describe the current implemented behavior of the API.

All responses are returned as plain text or JSON depending on the endpoint, as required by the challenge contract.

### Reset state

```http
POST /reset
```

Response:

* `200 OK`
* body: `OK`

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

## Atomic Operations with Redis Lua Scripts

Lua scripts are used for `withdraw` and `transfer` because both operations require multiple Redis steps to run atomically.

For `withdraw`, the script checks whether the origin account exists before decrementing the balance.

For `transfer`, the script checks the origin account, decrements the origin balance, and increments the destination balance in one atomic operation.

This keeps the implementation simple while avoiding partial state changes in multi-step updates.

---

## Automated Testing

Laravel Feature tests cover the challenge flow through real HTTP requests to the application:

* reset state
* balance lookup for missing and existing accounts
* deposit with accumulated balance
* withdraw for missing and existing origin accounts
* transfer for missing and existing origin accounts

Feature tests were chosen because they validate the application at the HTTP boundary while still running fast enough for local development and interview discussion.

---

## Running Tests

The recommended way to run the automated tests is inside the Docker application container, where the Redis extension and runtime match the project environment:

```bash
docker compose exec -T app php artisan test
```

To run only the banking API feature tests:

```bash
docker compose exec -T app php artisan test --filter=BankingApiTest
```

---

## Current Status

* [x] Dockerized environment
* [x] Application architecture
* [x] Redis repository implementation
* [x] Atomic operations (Lua scripts)
* [x] End-to-end API behavior
* [x] Automated tests

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

## Additional Documentation

For deeper technical insights and trade-offs, see:

- TECHNICAL_DECISIONS.md

---

## Author

João Victor Morais de Mello
