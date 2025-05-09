<?php

namespace DDTrace\Tests\Integrations\Laravel;

use DDTrace\Tests\Common\AppsecTestCase;
use DDTrace\Tests\Frameworks\Util\Request\GetSpec;
use datadog\appsec\AppsecStatus;

class AutomatedLoginEventsTestSuite extends AppsecTestCase
{
    public static $database = "laravel8";

    public static function getAppIndexScript()
    {
        return __DIR__ . '/../../../Frameworks/Laravel/Version_8_x/public/index.php';
    }

    protected function ddSetUp()
    {
        parent::ddSetUp();
        $this->connection()->exec("DELETE from users where email LIKE 'test-user%'");
        AppsecStatus::getInstance()->setDefaults();
    }

    protected function login($email)
    {
        $this->call(
            GetSpec::create('Login success event', '/login/auth?email='.$email)
        );
    }

    protected function createUser($id, $name, $email)
    {
        //Password is password
        $this->connection()->exec("insert into users (id, name, email, password) VALUES (".$id.", '".$name."', '".$email."', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')");
    }

    public function testUserLoginSuccessEvent()
    {
        $id = 1234;
        $name = 'someName';
        $email = 'test-user@email.com';
        $this->createUser($id, $name, $email);

        $this->login($email);

        $events = AppsecStatus::getInstance()->getEvents(['track_user_login_success_event_automated']);
        $this->assertEquals(1, count($events));
        $this->assertEquals($id, $events[0]['userId']);
        $this->assertEquals($email, $events[0]['userLogin']);
        $this->assertEquals($name, $events[0]['metadata']['name']);
        $this->assertEquals($email, $events[0]['metadata']['email']);
    }

    public function testUserLoginFailureEvent()
    {
        $email = 'test-user-non-existing@email.com';

        $this->login($email);

        $events = AppsecStatus::getInstance()->getEvents(['track_user_login_failure_event_automated']);
        $this->assertEquals(1, count($events));
        $this->assertEquals($email, $events[0]['userLogin']);
    }

    public function testUserSignUp()
    {
        $email = 'test-user-new@email.com';
        $name = 'somename';
        $password = 'somepassword';

        $this->call(
            GetSpec::create('Signup', sprintf('/login/signup?email=%s&name=%s&password=%s', $email, $name, $password))
        );

        $users = $this->connection()->query("SELECT * FROM users where email='".$email."'")->fetchAll();

        $this->assertEquals(1, count($users));

        $signUpEvent = AppsecStatus::getInstance()->getEvents(['track_user_signup_event_automated']);

        $this->assertEquals($users[0]['id'], $signUpEvent[0]['userId']);
        $this->assertEquals($users[0]['email'], $signUpEvent[0]['userLogin']);
    }

    public function testLoggedInCalls()
    {
        $this->enableSession();
        $id = 1234;
        $name = 'someName';
        $email = 'test-user@email.com';
        $this->createUser($id, $name, $email);

        //First log in
        $this->login($email);

        //Now we are logged in lets do another call
        AppsecStatus::getInstance()->setDefaults(); //Remove all events
        $this->call(GetSpec::create('Behind auth', '/behind_auth'));

        $loginEvents = AppsecStatus::getInstance()->getEvents([
            'track_user_login_success_event_automated',
            'track_user_login_failure_event_automated',
            'track_user_signup_event_automated'
        ]);

        $authenticatedEvents = AppsecStatus::getInstance()->getEvents([
            'track_authenticated_user_event_automated'
        ]);

        $this->assertEquals(0, count($loginEvents)); // Auth does not generate appsec events
        $this->assertEquals(1, count($authenticatedEvents));
        $this->assertEquals($id, $authenticatedEvents[0]['userId']);
        $this->disableSession();
    }
}
