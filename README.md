# Shortest Route Optimizer

A full-stack directed-graph shortest-path application built with a React frontend and a Laravel API. It calculates routes with Dijkstra's algorithm, displays the graph and highlighted route, and keeps a per-user calculation history.

## Features

- Register and log in with Laravel Sanctum bearer tokens
- Calculate the shortest directed route between graph nodes
- Visualize nodes, directed edges, distances, and the selected route
- View the latest 100 route calculations for the signed-in user
- Admin users can add nodes and outgoing connections

## Technology

Frontend: React 19
          Vite 8
          Tailwind CSS 4
          Dijkstra shortest-path service
          Node.js 20+ and npm

Backend:  PHP 8.2+
          Laravel 11
          MySQL
          PHPUnit


## Demo Accounts

Admin : `admin@example.com` | `password` 
User : `user@example.com` | `password` 

The admin account can create nodes and connections. The regular account can use the calculator and view its own history.


## Backend Setup

```bash
cd shortest-route-backend
composer install
cp .env.example .env
php artisan key:generate
```

### Configure MySQL in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

Then migrate and seed:

```bash
php artisan migrate --seed
```

Start the API:

```bash
php artisan serve
```

The API runs at `http://127.0.0.1:8000`.


## Frontend Setup

```bash
cd shortest-route-frontend
npm install
npm run dev
```

Open `http://localhost:5173`. Vite proxies `/api` requests to `http://127.0.0.1:8000`, so no frontend CORS configuration is required during development.


## API

All routes are under `/api`.

| Method | Endpoint | Authentication | Purpose |
| --- | --- | --- | --- |
| `POST` | `/register` | Public | Create an account and receive a token |
| `POST` | `/login` | Public | Authenticate and receive a token |
| `GET` | `/nodes` | Bearer token | List nodes and directed connections |
| `POST` | `/nodes` | Bearer token; admin | Add a node and outgoing connections |
| `POST` | `/shortest-path` | Bearer token | Calculate a shortest route |
| `GET` | `/shortest-path/history` | Bearer token | List the user's recent calculations |

Example request:

```json
POST /api/shortest-path
Authorization: Bearer <token>
Content-Type: application/json

{
  "fromNode": "A",
  "toNode": "D"
}
```

Successful response:

```json
{
  "nodeNames": ["A", "B", "C", "D"],
  "distance": 10
}
```

If no route exists, the API returns `{"nodeNames":[],"distance":0}`.

## Seeded Graph

The default graph is a directed example:

| From | To | Distance |
| --- | --- | --- |
| A | B | 4 |
| A | E | 7 |
| A | F | 6 |
| B | C | 3 |
| B | E | 2 |
| C | D | 3 |
| E | D | 5 |
| F | E | 4 |

For A to D, the shortest path is `A -> B -> C -> D`, with total distance `10`.

## Tests

Run backend unit and feature tests:

```bash
cd shortest-route-backend
php artisan test
```

## Console command

```bash
php artisan route:calculate A D
# A, B, C, D
# Total Distance: 10

php artisan route:calculate E B
# No path exists from E to B.
# Total Distance: 0
```

## Algorithm

`shortest-route-backend\app\Services\ShortestPathService::shortestPath(string $fromNodeName, string $toNodeName, array $graphNodes): array`

`GraphRepository::adjacency()` loads the directed graph from the database and passes it to the service as an adjacency map:

```php
[
    'A' => ['B' => 4, 'E' => 7, 'F' => 6],
    'B' => ['C' => 3, 'E' => 2],
    // ...
]
```

The method returns `['nodeNames' => ['A', 'B', 'C', 'D'], 'distance' => 10]`.

1. If either endpoint is not known from the source keys or target keys in the graph, return `['nodeNames' => [], 'distance' => 0]`.
2. Set `dist[$fromNodeName] = 0`; other distances are implicitly infinity. Insert the source into `SplPriorityQueue` with priority `0`.
3. PHP's `SplPriorityQueue` extracts the highest priority, so the service negates distances and uses `-candidate` as the priority.
4. Pop the next node and reverse its priority to obtain the tentative distance. If it is stale because a shorter distance was already recorded, skip it.
5. Stop when the destination is popped. For positive edge weights, its recorded distance is final.
6. Relax every outgoing edge `u → v (weight)`: if `dist[u] + weight < dist[v]`, update `dist[v]`, set `prev[v] = u`, and insert `v` with `-dist[v]`.
7. Rebuild the route by following `prev` backward from the destination to the source, then reverse the node names.
8. If the destination has no recorded distance, return `['nodeNames' => [], 'distance' => 0]`.

The seeded graph is loaded as a directed graph, so `B → E` does not imply `E → B`. The implementation has `O((V + E) log V)` time complexity and `O(V + E)` storage for the adjacency map and Dijkstra state.


## Assumptions
- Distances are positive integers (Dijkstra requires non-negative weights; `0` is rejected as well).
- One edge per ordered pair of nodes (`unique(from, to)`); self-loops are rejected.
- Connections stored in a separate `connections` table (`from_node_id`, `to_node_id`, `distance`) rather than a JSON column, so foreign keys and uniqueness are enforced.
- On node creation, `toNode` must already exist (as the spec says), so nodes are added "target first". The seeder inserts the example graph directly.
- "Admin" = `users.is_admin = true`. It is not mass-assignable, so `/api/register` can never create an admin; admins come from the seeder or the database.
- Same `fromNode` and `toNode` is rejected with 422 (the service itself returns `[node]`, distance `0`).
- Unreachable is a normal `200` response with an empty path, not an error; the UI shows "No path exists".
