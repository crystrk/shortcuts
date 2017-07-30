<?php
::L5 AUTH::

php artisan make:auth

# add some field on user migration
-- user migration
'username', 'own_password',
$table->string('username')->unique();
$table->boolean('own_password')->default(false);



-- Level
php artisan make:migration create_levels_table --create=levels

$table->increments('id');
$table->char('role', 100);
$table->char('sub_role', 100); //**

-- User To Role
php artisan make:migration create_user_to_level_table --create=user_to_level

$table->integer('user_id')->unsigned();
$table->integer('level_id')->unsigned();

$table->primary(['user_id', 'level_id']);
$table->foreign('user_id')->references('id')->on('users');
$table->foreign('level_id')->references('id')->on('levels');



-- make model
php artisan make:model Profiles\Level

protected $table = "levels";
protected $fillable = ['role','sub_role']; /** sub_role optional **/

public $timestamps = false;

    public function users()
    {
        return $this->belongsToMany(User::class,'user_to_level','level_id','user_id');
    }





# App\Profiles\User.php

protected $fillable = [
        'name', 'email','username', 'password', 'own_password',
];


    public function roles()
    {
        return $this->belongsToMany(Level::class,'user_to_level','user_id','level_id');
    }

    public function assignRole($role)
    {
        if (is_string($role)) {
            $role = Level::where('role', $role)->first();
        }

        return $this->roles()->attach($role);
    }

    public function revokeRole($role)
    {
        if (is_string($role)) {
            $role = Level::where('role', $role)->first();
        }

        return $this->roles()->detach($role);
    }

    public function hasRole($name)
    {
        foreach($this->roles as $role)
        {
            if ($role->role === $name) return true;
        }

        return false;
    }

/**
 * Seed
 */
php artisan make:seed LevelSeeder

$row = DB::table('levels')->count();
if($row < 1){
    DB::table('levels')->insert([
        'role' => 'admin',
        'sub_role' => 'admin', // optional
    ]);
    DB::table('levels')->insert([
        'role' => 'operator',
        'sub_role' => 'fitur-1', // optional
    ]);

}

php artisan make:seed UsersSeeder

$row = DB::table('users')->count();
if($row < 1){

    DB::table('users')->insert([
        'name' => 'Administrator',
        'username' => 'admin',
        'email' => 'admin@email.com',
        'password' => bcrypt('password'),
    ]);

    DB::table('users')->insert([
        'name' => 'Operator',
        'username' => 'operator',
        'email' => 'op@email.com',
        'password' => bcrypt('password'),
    ]);

}

php artisan make:seed UserToLevelSeeder

    $row = DB::table('user_to_level')->count();

    if($row < 1){

        DB::table('user_to_level')->insert([
            'user_id' => '1',
            'level_id' => '1',
        ]);

        DB::table('user_to_level')->insert([
            'user_id' => '2',
            'level_id' => '2',
        ]);

    }

// add DatabaseSeeder.php
$this->call(LevelSeeder::class);
$this->call(UsersSeeder::class);
$this->call(UserToLevelSeeder::class);

/**
 * LoginController.php
 */
protected $redirectTo = '/landing';


    public function username()
    {
        return 'username';
    }

/**
 * Auth\LandingController
 */
php artisan make:controller Auth\LandingController

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {

        if (\Auth::user()->hasRole('admin')) {

            return redirect(route('admin.index'));

        } elseif (\Auth::user()->hasRole('operator')) {

            return redirect(route('operator.index'));

        } else {

            return redirect(route('keluar'));
        }
    }

/**
 * custom logout route
 */
Auth::routes();

Route::get('keluar', '\App\Http\Controllers\Auth\LoginController@logout')->name('keluar');
Route::get('/landing', 'Auth\LandingController@index')->name('landing');

/**
 * make RoleMiddleware
 */
php artisan make:middleware RoleMiddleware

    public function handle($request, Closure $next)
    {
        $roles = $this->getMiddlewareParameterOnly(func_get_args());
        foreach($roles as $role)
        {
            if (auth()->check() && auth()->user()->hasRole($role))
            {
                return $next($request);
            }
        }
        return abort(401, 'Unauthorized');
    }

    protected function getMiddlewareParameterOnly($args)
    {
        array_shift($args); // Delete $request
        array_shift($args); // Delete Closure $next
        return $args;
    }


/**
 * App\Http\kernel.php => protected $routeMiddleware
 */

'role' => 'App\Http\Middleware\RoleMiddleware',

/**
 * Example route
 */
Route::group([
    'prefix' => 'admin',
    'middleware' => ['auth','role:admin']
    ], function () {


});

/**
 * fix Specified key was too long;
 * app\AppServicesProviders
 */
use Illuminate\Support\Facades\Schema;

    public function boot()
    {
        Schema::defaultStringLength(191);
    }
