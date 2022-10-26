<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Logs;
use App\Models\Books;
use App\Models\Issue;
use App\Models\Branch;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Models\StudentCategories;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Redirect;

class StudentController extends Controller
{
	public function __construct()
	{

		$this->filter_params = array('branch', 'year', 'category');
	}

	public function index()
	{
		$conditions = array(
			'approved'	=> 0,
			'rejected'	=> 0
		);

		$students = Student::join('branches', 'branches.id', '=', 'students.branch')
			->join('student_categories', 'student_categories.cat_id', '=', 'students.category')
			->select('student_id', 'first_name', 'last_name', 'student_categories.category', 'roll_num', 'branches.branch', 'year')
			->where($conditions)
			->orderBy('student_id');

		// $this->filterQuery($students);
		$students = $students->get();
		// dd($students);
		return $students;
	}

	public function studentByAttribute(Request $request)
	{
		// dd($request->branch );
		$conditions = array(
			'approved'	=> 0,
			'rejected'	=> 0
		);

		if ($request->branch != 0) {
			$students = Student::join('branches', 'branches.id', '=', 'students.branch')
				->join('student_categories', 'student_categories.cat_id', '=', 'students.category')
				->select('student_id', 'first_name', 'last_name', 'student_categories.category', 'roll_num', 'branches.branch', 'year')
				->where($conditions)
				->where('students.branch', $request->branch)
				->orderBy('student_id');
			$students = $students->get();
			return $students;
		} elseif ($request->category != 0) {
			$students = Student::join('branches', 'branches.id', '=', 'students.branch')
				->join('student_categories', 'student_categories.cat_id', '=', 'students.category')
				->select('student_id', 'first_name', 'last_name', 'student_categories.category', 'roll_num', 'branches.branch', 'year')
				->where($conditions)
				->where('students.category', $request->category)
				->orderBy('student_id');
			$students = $students->get();
			return $students;
		} elseif ($request->year != 0) {
			// dd($request->year );
			$students = Student::join('branches', 'branches.id', '=', 'students.branch')
				->join('student_categories', 'student_categories.cat_id', '=', 'students.category')
				->select('student_id', 'first_name', 'last_name', 'student_categories.category', 'roll_num', 'branches.branch', 'year')
				->where($conditions)
				->where('students.year', $request->year)
				->orderBy('student_id');
			$students = $students->get();

			return $students;
		}

		return "No Result Found";
	}


	public function create()
	{
		$conditions = array(
			'approved'	=> 1,
			'rejected'	=> 0
		);

		$students = Student::join('branches', 'branches.id', '=', 'students.branch')
			->join('student_categories', 'student_categories.cat_id', '=', 'students.category')
			->select('student_id', 'first_name', 'last_name', 'student_categories.category', 'roll_num', 'branches.branch', 'year', 'email_id', 'books_issued')
			->where($conditions)
			->orderBy('student_id');

		// $this->filterQuery($students);
		$students = $students->get();

		return $students;
	}


	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		$student = Student::find($id);
		if ($student == NULL) {
			throw new Exception('Invalid Student ID');
		}

		$student->year = (int)substr($student->year, 2, 4);

		$studentCategory = StudentCategories::find($student->category);
		$student->category = $studentCategory->category;

		$studentBranch = Branch::find($student->branch);
		$student->branch = $studentBranch->branch;


		if ($student->rejected == 1) {
			unset($student->approved);
			unset($student->books_issued);
			$student->rejected = (bool)$student->rejected;

			return $student;
		}

		if ($student->approved == 0) {
			unset($student->rejected);
			unset($student->books_issued);
			$student->approved = (bool)$student->approved;

			return $student;
		}

		unset($student->rejected);
		unset($student->approved);

		$studentIssuedBooks = Logs::select('book_issue_id', 'issued_at')
			->where('student_id', '=', $id)
			->orderBy('created_at', 'desc')
			->take($student->books_issued)
			->get();

		foreach ($studentIssuedBooks as $issuedBook) {
			$issue = Issue::find($issuedBook->book_issue_id);
			$book = Books::find($issue->book_id);
			$issuedBook->name = $book->title;

			$issuedBook->issued_at = date('d-M', strtotime($issuedBook->issued_at));
		}

		$student->issued_books = $studentIssuedBooks;

		return $student;
	}


	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update(Request $request, $id)
	{
		$flag = (bool)$request->get('flag');

		$student = Student::findOrFail($id);

		if ($flag) {
			// if student is approved
			$student->approved = 1;
			$student->save();

			return "Student's approval status successfully changed.";
		}

		$student->rejected = 1;
		$student->save();

		return "Student's rejection status successfully changed.";
	}


	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy(Request $request, $id)
	{
		// dd($request->all());
		if ($request->category) {

			$student = StudentCategories::find($id);
			$student->delete();
			if (!$student) {
				return "Student Category Fail to Delete!.";
			}

			return redirect(route('settings'));
		} elseif ($request->branch) {

			$branch = Branch::find($id);
			$branch->delete();
			if (!$branch) {
				return "School Branch Fail to Delete!.";
			}

			return redirect(route('settings'));
		}
	}


	public function renderStudents()
	{
		$dbControl = new HomeController;
		return view('panel.students')
			->with('branch_list', $dbControl->branch_list)
			->with('student_categories_list', $dbControl->student_categories_list);
	}

	public function renderApprovalStudents()
	{
		$dbControl = new HomeController;
		return view('panel.approval')
			->with('branch_list', $dbControl->branch_list)
			->with('student_categories_list', $dbControl->student_categories_list);
	}

	public function getRegistration()
	{
		$dbControl = new HomeController;
		return view('public.registration')
			->with('branch_list', $dbControl->branch_list)
			->with('student_categories_list', $dbControl->student_categories_list);
	}

	public function postRegistration(Request $request)
	{

		$validator = $request->validate([

			'first'			=> 'required|alpha',
			'last'			=> 'required|alpha',
			'rollnumber'	=> 'required|integer',
			'branch'		=> 'required|between:0,10',
			'year'			=> 'required|integer',
			'email'			=> 'required|email',
			'category'		=> 'required|between:0,5'

		]);

		if (!$validator) {
			return Redirect::route('student-registration')
				->withErrors($validator)
				->withInput();   // fills the field with the old inputs what were correct
		}

		$student = Student::create(array(
			'first_name'	=> $request->get('first'),
			'last_name'		=> $request->get('last'),
			'category'		=> $request->get('category'),
			'roll_num'		=> $request->get('rollnumber'),
			'branch'		=> $request->get('branch'),
			'year'			=> $request->get('year'),
			'email_id'		=> $request->get('email'),
		));

		if ($student) {
			return Redirect::route('student-registration')
				->with('global', 'Your request has been raised, you will be soon approved!');
		}
	}

	public function setting()
	{
		$branches = Branch::all();
		$studentCategory = StudentCategories::all();

		return view('panel.addsettings')
			->with('branches', $branches)
			->with('student_category', $studentCategory);
	}

	public function storeSetting(Request $request)
	{
		// dd($request->all());
		if ($request->category) {

			$student = StudentCategories::create($request->all());

			if (!$student) {
				return "Student Category Fail to Save!.";
			}

			return "Student Category Save Succesfully!.";
		} elseif ($request->branch) {

			$branch = Branch::create($request->all());

			if (!$branch) {
				return "School Branch Fail to Save!.";
			}

			return "School Branch Save Succesfully!.";
		}
	}
}
