<?php  namespace App\Http\Controllers\Admin;

use App\Gateways\DbUserGateway;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;

class UsersController extends AdminController
{
    use ValidatesRequests;

    protected $viewNamespace = 'admin.users';

    protected $defaultTitle = 'Users';

    /**
     * @var \App\Gateways\DbUserGateway
     */
    protected $user;
    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    protected $rules = [
        'email' => 'required|email',
    ];

    protected $storeRules = [
        'email' => 'required|email|unique:users',
    ];

    /**
     * @var \Illuminate\Contracts\Auth\Guard
     */
    private $guard;

    public function __construct(DbUserGateway $user, Request $request, Guard $guard)
    {
        $this->user = $user;
        $this->request = $request;
        $this->guard = $guard;
    }

    public function index()
    {
        $users = $this->user->all();

        return $this->render('index', compact('users'));
    }

    public function create()
    {
        $user = $this->user->newInstance();

        return $this->userForm('create', $user, 'New User');
    }

    public function store()
    {
        $this->validate($this->request, $this->storeRules);

        $this->user->createWithRandomPassword($this->request->only('email', 'name'));

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function edit($id)
    {
        $user = $this->user->find($id);

        return $this->userForm('edit', $user);
    }

    public function profile()
    {
        return $this->userForm('edit', $this->guard->user());
    }

    public function userForm($action, $user, $title = null)
    {
        $title = $title ?: 'Editing User - ' . $user->name;

        switch ($action) {
            case 'create':
                $method = null;
                $route = route('admin.users.store');
                break;
            case 'edit':
                $method = 'put';
                $route = route('admin.users.update', $user);
        }

        return $this->render('form', compact('user', 'method', 'route'), $title);
    }

    public function update($id)
    {
        $this->validate($this->request);

        $response = $this->user->update($id, $this->request->only('email', 'name'));

        if ($response) {
            return redirect()->route('admin.users.index')
                ->with('success', 'User updated successfully.');
        }
    }

    /**
     * Validate the given request with the given rules.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  array $rules
     * @return void
     */
    public function validate(Request $request, array $rules = null)
    {
        $rules = $rules ?: $this->rules;

        $validator = $this->getValidationFactory()->make($request->all(), $rules);

        if ($validator->fails()) {
            $this->throwValidationException($request, $validator);
        }
    }

    /**
     * Create the response for when a request fails validation.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  array $errors
     * @return \Illuminate\Http\Response
     */
    protected function buildFailedValidationResponse(Request $request, array $errors)
    {
        if ($request->ajax()) {
            return new JsonResponse($errors, 422);
        }

        return redirect()->back()
            ->withInput($request->input())
            ->with('danger', 'The information you entered was not valid.')
            ->withErrors($errors);
    }
}
