<?php

namespace App\Http\Controllers;

use App\Models\Logs;
use App\Models\Books;
use App\Models\Issue;
use App\Models\Branch;
use App\Models\Student;
use App\Models\Categories;
use Illuminate\Http\Request;
use App\Models\BookCategories;
use App\Models\StudentCategories;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use Exception;

class BooksController extends Controller
{
	public function __construct()
	{

		$this->filter_params = array('category_id');
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{

		$bookList = Books::select('book_id', 'title', 'author', 'description', 'book_categories.category')
			->join('book_categories', 'book_categories.id', '=', 'books.category_id')
			->orderBy('book_id')->get();
		// dd($bookList);
		// $this->filterQuery($bookList);

		// $bookList = $bookList->get();

		$bookSize = count($bookList);
		for ($i = 0; $i < $bookSize; $i++) {
			$id = $bookList[$i]['book_id'];
			$conditions = array(
				'book_id'			=> $id,
				'available_status'	=> 1
			);

			$bookList[$i]['total_books'] = Issue::select()
				->where('book_id', '=', $id)
				->count();

			$bookList[$i]['avaliable'] = Issue::select()
				->where($conditions)
				->count();
		}

		return $bookList;
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store(Request $request)
	{
		$books = $request->all();

		// DB::transaction( function() use($books) {
		// dd($books);
		$userId = Auth::id();
		$bookTitle = Books::create([
			'title'			=> $books['title'],
			'author'		=> $books['author'],
			'description' 	=> $books['description'],
			'category_id'	=> $books['category_id'],
			'added_by'		=> $userId
		]);
		// dd($bookTitle);
		$newId = $bookTitle->book_id;

		$message = 'Invalid update data provided';
		if (!$bookTitle) {
			return $message;
		}

		$numberOfIssues = $books['number'];

		$dbFlag = false;
		for ($i = 0; $i < $numberOfIssues; $i++) {
			$issues = Issue::create([
				'book_id'	=> $newId,
				'added_by'	=> $userId
			]);

			if (!$issues) {
				$dbFlag = true;
			}
		}

		if ($dbFlag) {
			return $message;
		}

		return "Books Added successfully to Database";
	}


	public function bookCategoryStore(Request $request)
	{
		$bookcategory = BookCategories::create($request->all());

		if (!$bookcategory) {
			return 'Book Category fail to save!';
		}

		return "Book Category Added successfully to Database";
	}


	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($string)
	{
		$bookList = Books::select('book_id', 'title', 'author', 'description', 'book_categories.category')
			->join('book_categories', 'book_categories.id', '=', 'books.category_id')
			->where('title', 'like', '%' . $string . '%')
			->orWhere('author', 'like', '%' . $string . '%')
			->orderBy('book_id');

		$bookList = $bookList->get();

		foreach ($bookList as $book) {
			$conditions = array(
				'book_id'			=> $book->book_id,
				'available_status'	=> 1
			);

			$count = Issue::where($conditions)
				->count();

			$book->avaliability = ($count > 0) ? true : false;
		}

		return $bookList;
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		$issue = Issue::find($id);
		if ($issue == NULL) {
			return 'Invalid Book ID';
		}

		$book = Books::find($issue->book_id);

		$issue->book_name = $book->title;
		$issue->author = $book->author;

		$issue->category = Categories::find($book->category_id)
			->category;

		$issue->available_status = (bool)$issue->available_status;
		if ($issue->available_status == 1) {
			return $issue;
		}

		$conditions = array(
			'return_time'	=> 0,
			'book_issue_id'	=> $id,
		);
		$bookIssueLog = Logs::where($conditions)
			->take(1)
			->get();

		foreach ($bookIssueLog as $log) {
			$studentId = $log->student_id;
		}

		$studentData = Student::find($studentId);

		unset($studentData->email_id);
		unset($studentData->books_issued);
		unset($studentData->approved);
		unset($studentData->rejected);

		$studentBranch = Branch::find($studentData->branch)
			->branch;
		$rollNum = $studentData->roll_num . '/' . $studentBranch . '/' . substr($studentData->year, 2, 4);

		unset($studentData->roll_num);
		unset($studentData->branch);
		unset($studentData->year);

		$studentData->roll_num = $rollNum;

		$studentData->category = StudentCategories::find($studentData->category)
			->category;
		$issue->student = $studentData;


		return $issue;
	}

	public function renderAddBookCategory()
	{
		return view('panel.addbookcategory');
	}

	public function renderAddBooks()
	{
		$dbControl = new HomeController();

		return view('panel.addbook')
			->with('categories_list', $dbControl->categories_list);
	}

	public function renderAllBooks()
	{
		$dbControl = new HomeController();

		return view('panel.allbook')
			->with('categories_list', $dbControl->categories_list);
	}

	public function bookByCategory($catId)
	{
		$bookList = Books::select('book_id', 'title', 'author', 'description', 'book_categories.category')
			->join('book_categories', 'book_categories.id', '=', 'books.category_id')
			->where('category_id', $catId)->orderBy('book_id');

		$bookList = $bookList->get();

		$bookSize = count($bookList);
		for ($i = 0; $i < $bookSize; $i++) {

			$id = $bookList[$i]['book_id'];
			$conditions = array(
				'book_id'			=> $id,
				'available_status'	=> 1
			);

			$bookList[$i]['total_books'] = Issue::select()
				->where('book_id', '=', $id)
				->count();

			$bookList[$i]['avaliable'] = Issue::select()
				->where($conditions)
				->count();
		}

		return $bookList;
	}

	public function searchBook()
	{
		$dbControl = new HomeController();

		return view('public.book-search')
			->with('categories_list', $dbControl->categories_list);
	}
}
