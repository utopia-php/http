<?php

namespace Utopia\Tests;

use PHPUnit\Framework\TestCase;
use Utopia\Http;
use Utopia\Model;
use Utopia\Adapter\FPM\Request;
use Utopia\Adapter\FPM\Response;
use Utopia\Validator;
use Utopia\Validator\ArrayList;
use Utopia\Validator\Text;

// Test Model implementation
class UserModel implements Model
{
    final public function __construct(
        public string $name,
        public int $age,
        public ?string $email = null
    ) {
    }

    public static function fromArray(array $value): static
    {
        if (!isset($value['name']) || !isset($value['age'])) {
            throw new \InvalidArgumentException('Missing required fields: name and age');
        }
        return new static(
            $value['name'],
            $value['age'],
            $value['email'] ?? null
        );
    }
}

// Another test model with nested data
class AddressModel implements Model
{
    final public function __construct(
        public string $street,
        public string $city,
        public string $zipCode,
        public ?string $country = 'USA'
    ) {
    }

    public static function fromArray(array $value): static
    {
        return new static(
            $value['street'] ?? '',
            $value['city'] ?? '',
            $value['zipCode'] ?? '',
            $value['country'] ?? 'USA'
        );
    }
}

// Custom validator for Model objects
class UserValidator extends Validator
{
    public function __construct()
    {
    }

    public function getDescription(): string
    {
        return 'Validates a UserModel instance';
    }

    public function isValid(mixed $value): bool
    {
        return $value instanceof UserModel;
    }

    public function getType(): string
    {
        return 'model';
    }

    public function isArray(): bool
    {
        return false;
    }
}

class AddressValidator extends Validator
{
    public function __construct()
    {
    }

    public function getDescription(): string
    {
        return 'Validates an AddressModel instance';
    }

    public function isValid(mixed $value): bool
    {
        return $value instanceof AddressModel;
    }

    public function getType(): string
    {
        return 'model';
    }

    public function isArray(): bool
    {
        return false;
    }
}

class ModelTest extends TestCase
{
    protected ?Http $app;
    protected ?string $method;
    protected ?string $uri;

    public function setUp(): void
    {
        Http::reset();
        $this->app = new Http('UTC');
        $this->saveRequest();
    }

    public function tearDown(): void
    {
        $this->app = null;
        $this->restoreRequest();
    }

    protected function saveRequest(): void
    {
        $this->method = $_SERVER['REQUEST_METHOD'] ?? null;
        $this->uri = $_SERVER['REQUEST_URI'] ?? null;
    }

    protected function restoreRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = $this->method;
        $_SERVER['REQUEST_URI'] = $this->uri;
        $_GET = [];
        $_POST = [];
    }

    public function testModelParamWithJsonString(): void
    {
        $result = null;

        $this->app
            ->get('/users')
            ->param('user', null, new UserValidator(), 'User data', false, [], false, false, '', UserModel::class)
            ->action(function (UserModel $user) use (&$result) {
                $result = $user;
            });

        // Test with JSON string input
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users';
        $_GET = ['user' => '{"name":"John Doe","age":30,"email":"john@example.com"}'];

        $this->app->run(new Request(), new Response());

        $this->assertInstanceOf(UserModel::class, $result);
        $this->assertEquals('John Doe', $result->name);
        $this->assertEquals(30, $result->age);
        $this->assertEquals('john@example.com', $result->email);
    }

    public function testModelParamWithArray(): void
    {
        $result = null;

        $this->app
            ->post('/users')
            ->param('user', null, new UserValidator(), 'User data', false, [], false, false, '', UserModel::class)
            ->action(function (UserModel $user) use (&$result) {
                $result = $user;
            });

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/users';
        $_POST = ['user' => ['name' => 'Jane Smith', 'age' => 25]];

        $this->app->run(new Request(), new Response());

        $this->assertInstanceOf(UserModel::class, $result);
        $this->assertEquals('Jane Smith', $result->name);
        $this->assertEquals(25, $result->age);
        $this->assertNull($result->email);
    }

    public function testModelParamWithInvalidJson(): void
    {
        $errorCaught = false;
        $errorMessage = '';

        $this->app
            ->get('/users')
            ->param('user', null, new UserValidator(), 'User data', false, [], false, false, '', UserModel::class)
            ->action(function (UserModel $user) {
                // Should not reach here
            });

        $this->app->error()->inject('error')->action(function (\Throwable $error) use (&$errorCaught, &$errorMessage) {
            $errorCaught = true;
            $errorMessage = $error->getMessage();
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users';
        $_GET = ['user' => '{invalid json}'];

        $this->app->run(new Request(), new Response());

        $this->assertTrue($errorCaught);
        $this->assertStringContainsString('Failed to parse JSON', $errorMessage);
    }

    public function testModelParamWithMissingRequiredFields(): void
    {
        $errorCaught = false;
        $errorMessage = '';

        $this->app
            ->post('/users')
            ->param('user', null, new UserValidator(), 'User data', false, [], false, false, '', UserModel::class)
            ->action(function (UserModel $user) {
                // Should not reach here
            });

        $this->app->error()->inject('error')->action(function (\Throwable $error) use (&$errorCaught, &$errorMessage) {
            $errorCaught = true;
            $errorMessage = $error->getMessage();
        });

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/users';
        $_POST = ['user' => '{"name":"John"}']; // Missing 'age' field

        $this->app->run(new Request(), new Response());

        $this->assertTrue($errorCaught);
        $this->assertStringContainsString('Failed to create model instance', $errorMessage);
    }

    public function testModelParamWithInvalidType(): void
    {
        $errorCaught = false;
        $errorMessage = '';

        $this->app
            ->get('/users-invalid-type')
            ->param('user', null, new UserValidator(), 'User data', false, [], false, false, '', UserModel::class)
            ->action(function (UserModel $user) {
                // Should not reach here
            });

        $this->app->error()->inject('error')->action(function (\Throwable $error) use (&$errorCaught, &$errorMessage) {
            $errorCaught = true;
            $errorMessage = $error->getMessage();
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users-invalid-type';
        $_GET = ['user' => 12345]; // Invalid type (number instead of JSON string/array)

        $this->app->run(new Request(), new Response());

        $this->assertTrue($errorCaught);
        $this->assertStringContainsString('must be a JSON string, or an array', $errorMessage);
    }

    public function testModelParamOptional(): void
    {
        $result = null;
        $actionCalled = false;

        $this->app
            ->get('/users-optional')
            ->param('user', null, new UserValidator(), 'User data', true, [], false, false, '', UserModel::class)
            ->action(function (?UserModel $user = null) use (&$result, &$actionCalled) {
                $actionCalled = true;
                $result = $user;
            });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users-optional';
        $_GET = [];
        // Not providing 'user' param

        $this->app->run(new Request(), new Response());

        $this->assertTrue($actionCalled);
        $this->assertNull($result);
    }

    public function testModelParamWithDefault(): void
    {
        $result = null;
        $defaultUser = new UserModel('Default User', 0);

        $this->app
            ->get('/users-default')
            ->param('user', $defaultUser, new UserValidator(), 'User data', true, [], false, false, '', UserModel::class)
            ->action(function (UserModel $user) use (&$result) {
                $result = $user;
            });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users-default';
        $_GET = [];
        // Not providing 'user' param, should use default

        $this->app->run(new Request(), new Response());

        $this->assertInstanceOf(UserModel::class, $result);
        $this->assertEquals('Default User', $result->name);
        $this->assertEquals(0, $result->age);
    }

    public function testMultipleModelParams(): void
    {
        $userResult = null;
        $addressResult = null;

        $this->app
            ->post('/profile')
            ->param('user', null, new UserValidator(), 'User data', false, [], false, false, '', UserModel::class)
            ->param('address', null, new AddressValidator(), 'Address data', false, [], false, false, '', AddressModel::class)
            ->action(function (UserModel $user, AddressModel $address) use (&$userResult, &$addressResult) {
                $userResult = $user;
                $addressResult = $address;
            });

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/profile';
        $_POST = [
            'user' => '{"name":"Alice","age":28}',
            'address' => '{"street":"123 Main St","city":"New York","zipCode":"10001"}'
        ];

        $this->app->run(new Request(), new Response());

        $this->assertInstanceOf(UserModel::class, $userResult);
        $this->assertEquals('Alice', $userResult->name);
        $this->assertEquals(28, $userResult->age);

        $this->assertInstanceOf(AddressModel::class, $addressResult);
        $this->assertEquals('123 Main St', $addressResult->street);
        $this->assertEquals('New York', $addressResult->city);
        $this->assertEquals('10001', $addressResult->zipCode);
        $this->assertEquals('USA', $addressResult->country);
    }

    public function testInvalidModelClass(): void
    {
        $errorCaught = false;
        $errorMessage = '';

        $this->app
            ->get('/test')
            ->param('data', null, new Text(100), 'Test data', false, [], false, false, '', 'NonExistentClass')
            ->action(function ($data) {
                // Should not reach here
            });

        $this->app->error()->inject('error')->action(function (\Throwable $error) use (&$errorCaught, &$errorMessage) {
            $errorCaught = true;
            $errorMessage = $error->getMessage();
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test';
        $_GET = ['data' => '{"test":"value"}'];

        $this->app->run(new Request(), new Response());

        $this->assertTrue($errorCaught);
        $this->assertStringContainsString('Model class does not exist', $errorMessage);
    }

    public function testNonModelClass(): void
    {
        $errorCaught = false;
        $errorMessage = '';

        $this->app
            ->get('/test-non-model')
            ->param('data', null, new Text(100), 'Test data', false, [], false, false, '', \stdClass::class)
            ->action(function ($data) {
                // Should not reach here
            });

        $this->app->error()->inject('error')->action(function (\Throwable $error) use (&$errorCaught, &$errorMessage) {
            $errorCaught = true;
            $errorMessage = $error->getMessage();
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/test-non-model';
        $_GET = ['data' => '{"test":"value"}'];

        $this->app->run(new Request(), new Response());

        $this->assertTrue($errorCaught);
        $this->assertStringContainsString('not an instance of Utopia\\Model', $errorMessage);
    }

    public function testModelWithEmptyString(): void
    {
        $result = null;
        $actionCalled = false;

        $this->app
            ->get('/users-empty')
            ->param('user', null, new UserValidator(), 'User data', true, [], false, false, '', UserModel::class)
            ->action(function (?UserModel $user = null) use (&$result, &$actionCalled) {
                $actionCalled = true;
                $result = $user;
            });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/users-empty';
        $_GET = ['user' => '']; // Empty string

        $this->app->run(new Request(), new Response());

        $this->assertTrue($actionCalled);
        $this->assertNull($result);
    }

    public function testArrayListModelParamWithJsonString(): void
    {
        $result = null;

        $this->app
            ->post('/users-array')
            ->param('users', [], new ArrayList(new UserValidator()), 'Array of users', false, [], false, false, '', UserModel::class)
            ->action(function (array $users) use (&$result) {
                $result = $users;
            });

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/users-array';
        $_POST = ['users' => '[{"name":"John Doe","age":30},{"name":"Jane Smith","age":25}]'];

        $this->app->run(new Request(), new Response());

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(UserModel::class, $result[0]);
        $this->assertInstanceOf(UserModel::class, $result[1]);
        $this->assertEquals('John Doe', $result[0]->name);
        $this->assertEquals(30, $result[0]->age);
        $this->assertEquals('Jane Smith', $result[1]->name);
        $this->assertEquals(25, $result[1]->age);
    }

    public function testArrayListModelParamWithArray(): void
    {
        $result = null;

        $this->app
            ->post('/users-array-native')
            ->param('users', [], new ArrayList(new UserValidator()), 'Array of users', false, [], false, false, '', UserModel::class)
            ->action(function (array $users) use (&$result) {
                $result = $users;
            });

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/users-array-native';
        $_POST = ['users' => [
            ['name' => 'Alice', 'age' => 28, 'email' => 'alice@example.com'],
            ['name' => 'Bob', 'age' => 35]
        ]];

        $this->app->run(new Request(), new Response());

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertInstanceOf(UserModel::class, $result[0]);
        $this->assertInstanceOf(UserModel::class, $result[1]);
        $this->assertEquals('Alice', $result[0]->name);
        $this->assertEquals('alice@example.com', $result[0]->email);
        $this->assertEquals('Bob', $result[1]->name);
        $this->assertNull($result[1]->email);
    }

    public function testArrayListModelParamEmpty(): void
    {
        $result = null;

        $this->app
            ->post('/users-array-empty')
            ->param('users', [], new ArrayList(new UserValidator()), 'Array of users', false, [], false, false, '', UserModel::class)
            ->action(function (array $users) use (&$result) {
                $result = $users;
            });

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/users-array-empty';
        $_POST = ['users' => '[]'];

        $this->app->run(new Request(), new Response());

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testArrayListModelParamWithInvalidElement(): void
    {
        $errorCaught = false;
        $errorMessage = '';

        $this->app
            ->post('/users-array-invalid')
            ->param('users', [], new ArrayList(new UserValidator()), 'Array of users', false, [], false, false, '', UserModel::class)
            ->action(function (array $users) {
                // Should not reach here
            });

        $this->app->error()->inject('error')->action(function (\Throwable $error) use (&$errorCaught, &$errorMessage) {
            $errorCaught = true;
            $errorMessage = $error->getMessage();
        });

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/users-array-invalid';
        $_POST = ['users' => '[{"name":"John"}]']; // Missing 'age' field

        $this->app->run(new Request(), new Response());

        $this->assertTrue($errorCaught);
        $this->assertStringContainsString('Failed to create model instance', $errorMessage);
    }

    public function testArrayListModelParamOptional(): void
    {
        $result = null;
        $actionCalled = false;

        $this->app
            ->post('/users-array-optional')
            ->param('users', null, new ArrayList(new UserValidator()), 'Array of users', true, [], false, false, '', UserModel::class)
            ->action(function (?array $users = null) use (&$result, &$actionCalled) {
                $actionCalled = true;
                $result = $users;
            });

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/users-array-optional';
        $_POST = [];

        $this->app->run(new Request(), new Response());

        $this->assertTrue($actionCalled);
        $this->assertNull($result);
    }

    public function testArrayListModelParamWithDefault(): void
    {
        $result = null;
        $defaultUsers = [new UserModel('Default User', 0)];

        $this->app
            ->post('/users-array-default')
            ->param('users', $defaultUsers, new ArrayList(new UserValidator()), 'Array of users', true, [], false, false, '', UserModel::class)
            ->action(function (array $users) use (&$result) {
                $result = $users;
            });

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/users-array-default';
        $_POST = [];

        $this->app->run(new Request(), new Response());

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(UserModel::class, $result[0]);
        $this->assertEquals('Default User', $result[0]->name);
    }
}
