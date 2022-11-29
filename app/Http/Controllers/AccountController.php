<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountController extends Controller
{
	### Sign In
	/* After submitting the sign-in form */
	public function postSignIn(Request $request): RedirectResponse
	{
		$validator = $request->validate([
			'username' 	=> 'required',
			'password'	=> 'required'

		]);

		if (!$validator) {
			// Redirect to the sign in page
			return Redirect::route('account-sign-in')
				->withErrors($validator)
				->withInput();   // redirect the input
		}

		$remember = ($request->has('remember')) ? true : false;
		$auth = Auth::attempt(array(
			'username' => $request->get('username'),
			'password' => $request->get('password')
		), $remember);

		if ($auth) {
			return Redirect::intended('home');
		}

		return Redirect::route('account-sign-in')
			->with('global', 'There is a problem. Have you activated your account? or maybe Wrong Email or Wrong Password.');
	}

	/* Submitting the Create User form (POST) */
	public function postCreate(Request $request): RedirectResponse
	{
		// dd($request->all());
		$validator = $request->validate([
			'username'		=> 'required|max:20|min:3|unique:users',
			'password'		=> 'required',
			'password_again' => 'required|same:password'
		]);

		if (!$validator) {
			return Redirect::route('account-create')
				->withErrors($validator)
				->withInput();   // fills the field with the old inputs what were correct
		}

		// create an account
		$username	= $request->get('username');
		$password 	= $request->get('password');

		$userdata = User::create([
			'username' 	=> $username,
			'password' 	=> Hash::make($password)	// Changed the default column for Password
		]);

		if ($userdata) {
			return Redirect::route('account-sign-in')
				->with('global', 'Your account has been created. We have sent you an email to activate your account');
		}

		return Redirect::route('account-sign-in')
			->with('global', 'Failed, please try again!');
	}

	public function getSignIn(): View
	{
		return view('account.signin');
	}

	/* Viewing the form (GET) */
	public function getCreate(): View
	{
		return view('account.create');
	}

	### Sign Out
	public function getSignOut(): RedirectResponse
	{
		Auth::logout();
		return Redirect::route('account-sign-in');
	}
}