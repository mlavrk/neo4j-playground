<?php
// php-api/src/SocialNetworkAPI.php
namespace App;

use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Databags\Statement;

class SocialNetworkAPI
{
    private $client;

    public function __construct()
    {
        $host = $_ENV['NEO4J_HOST'] ?? 'neo4j';
        $port = $_ENV['NEO4J_PORT'] ?? '7687';
        $user = $_ENV['NEO4J_USER'] ?? 'neo4j';
        $password = $_ENV['NEO4J_PASSWORD'] ?? 'password123';

        $this->client = ClientBuilder::create()
            ->withDriver('bolt', "bolt://{$user}:{$password}@{$host}:{$port}")
            ->build();
    }

    public function createUser(string $name, string $email = '', ?int $age = null): array
    {
        $query = 'CREATE (u:User {name: $name, email: $email, age: $age, created_at: datetime()}) RETURN u';
        $result = $this->client->run($query, [
            'name' => $name,
            'email' => $email,
            'age' => $age
        ]);

        $record = $result->first();
        return $this->nodeToArray($record->get('u'));
    }

    public function getUser(string $name): ?array
    {
        $query = 'MATCH (u:User {name: $name}) RETURN u';
        $result = $this->client->run($query, ['name' => $name]);

        if ($result->count() === 0) {
            return null;
        }

        $record = $result->first();
        return $this->nodeToArray($record->get('u'));
    }

    public function getAllUsers(): array
    {
        $query = 'MATCH (u:User) RETURN u ORDER BY u.name';
        $result = $this->client->run($query);

        $users = [];
        foreach ($result as $record) {
            $users[] = $this->nodeToArray($record->get('u'));
        }

        return $users;
    }

    public function createFriendship(string $user1, string $user2): array
    {
        $query = '
            MATCH (u1:User {name: $user1}), (u2:User {name: $user2})
            CREATE (u1)-[r:FRIENDS {created_at: datetime()}]->(u2)
            CREATE (u2)-[r2:FRIENDS {created_at: datetime()}]->(u1)
            RETURN u1, u2, r
        ';

        $result = $this->client->run($query, [
            'user1' => $user1,
            'user2' => $user2
        ]);

        if ($result->count() === 0) {
            throw new \Exception("One or both users not found");
        }

        $record = $result->first();
        return [
            'user1' => $this->nodeToArray($record->get('u1')),
            'user2' => $this->nodeToArray($record->get('u2')),
            'relationship' => 'FRIENDS'
        ];
    }

    public function getFriends(string $userName): array
    {
        $query = '
            MATCH (u:User {name: $userName})-[:FRIENDS]->(friend:User)
            RETURN friend ORDER BY friend.name
        ';

        $result = $this->client->run($query, ['userName' => $userName]);

        $friends = [];
        foreach ($result as $record) {
            $friends[] = $this->nodeToArray($record->get('friend'));
        }

        return $friends;
    }

    public function getFriendRecommendations(string $userName, int $limit = 5): array
    {
        $query = '
            MATCH (u:User {name: $userName})-[:FRIENDS]->(friend)-[:FRIENDS]->(recommendation)
            WHERE NOT (u)-[:FRIENDS]->(recommendation) AND u <> recommendation
            RETURN recommendation, count(*) as mutual_friends
            ORDER BY mutual_friends DESC, recommendation.name
            LIMIT $limit
        ';

        $result = $this->client->run($query, [
            'userName' => $userName,
            'limit' => $limit
        ]);

        $recommendations = [];
        foreach ($result as $record) {
            $recommendations[] = [
                'user' => $this->nodeToArray($record->get('recommendation')),
                'mutual_friends' => $record->get('mutual_friends')->toInteger()
            ];
        }

        return $recommendations;
    }

    public function getShortestPath(string $user1, string $user2): ?array
    {
        $query = '
            MATCH path = shortestPath((u1:User {name: $user1})-[:FRIENDS*]-(u2:User {name: $user2}))
            RETURN path
        ';

        $result = $this->client->run($query, [
            'user1' => $user1,
            'user2' => $user2
        ]);

        if ($result->count() === 0) {
            return null;
        }

        $record = $result->first();
        $path = $record->get('path');

        $nodes = [];
        foreach ($path->nodes() as $node) {
            $nodes[] = $this->nodeToArray($node);
        }

        return [
            'path' => $nodes,
            'length' => count($nodes) - 1
        ];
    }

    public function deleteFriendship(string $user1, string $user2): array
    {
        $query = '
            MATCH (u1:User {name: $user1})-[r1:FRIENDS]->(u2:User {name: $user2})
            MATCH (u2)-[r2:FRIENDS]->(u1)
            DELETE r1, r2
            RETURN u1.name as user1, u2.name as user2
        ';

        $result = $this->client->run($query, [
            'user1' => $user1,
            'user2' => $user2
        ]);

        if ($result->count() === 0) {
            throw new \Exception("Friendship not found");
        }

        return ['message' => 'Friendship deleted successfully'];
    }

    public function clearDatabase(): array
    {
        $this->client->run('MATCH (n) DETACH DELETE n');
        return ['message' => 'Database cleared successfully'];
    }

    public function seedSampleData(): array
    {
        // Clear existing data
        $this->clearDatabase();

        // Create users
        $users = [
            'Alice', 'Bob', 'Charlie', 'Diana', 'Eve', 'Frank', 'Grace', 'Henry'
        ];

        foreach ($users as $user) {
            $this->createUser($user, strtolower($user) . '@example.com', rand(20, 40));
        }

        // Create friendships
        $friendships = [
            ['Alice', 'Bob'],
            ['Alice', 'Charlie'],
            ['Bob', 'Diana'],
            ['Charlie', 'Diana'],
            ['Diana', 'Eve'],
            ['Eve', 'Frank'],
            ['Frank', 'Grace'],
            ['Grace', 'Henry'],
            ['Henry', 'Alice'],
            ['Bob', 'Grace'],
        ];

        foreach ($friendships as $friendship) {
            $this->createFriendship($friendship[0], $friendship[1]);
        }

        return [
            'message' => 'Sample data created successfully',
            'users_created' => count($users),
            'friendships_created' => count($friendships)
        ];
    }

    private function nodeToArray($node): array
    {
        $properties = [];
        foreach ($node->getProperties() as $key => $value) {
            if (is_object($value) && method_exists($value, 'toString')) {
                $properties[$key] = $value->toString();
            } else {
                $properties[$key] = $value;
            }
        }
        return $properties;
    }
}