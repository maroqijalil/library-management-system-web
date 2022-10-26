<?php

namespace App\Http\Controllers;

use App\Models\Logs;
use App\Models\Books;
use App\Models\Issue;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Models\StudentCategories;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Response;
use Illuminate\View\View;

class LogController extends Controller
{
	public function index(): Response
	{
		$logs = Logs::select('id', 'book_issue_id', 'student_id', 'issued_at')
			->where('return_time', '=', 0)
			->orderBy('issued_at', 'DESC');

		$logs = $logs->get();

		$logSize = count($logs);
		for ($i = 0; $i < $logSize; $i++) {

			$issueId = $logs[$i]['book_issue_id'];
			$studentId = $logs[$i]['student_id'];

			// to get the name of the book from book issue id
			$issue = Issue::find($issueId);
			$bookId = $issue->book_id;
			$book = Books::find($bookId);
			$logs[$i]['book_name'] = $book->title;

			// to get the name of the student from student id
			$student = Student::find($studentId);
			$logs[$i]['student_name'] = $student->first_name . ' ' . $student->last_name;

			// change issue date and return date in human readable format
			$logs[$i]['issued_at'] = date('d-M', strtotime($logs[$i]['issued_at']));
			if ($issue->return_time == 0) {
				$logs[$i]['return_time'] =  '<p class="color:red">Pending</p>';
				continue;
			}

			$logs[$i]['return_time'] = date('d-M', strtotime($logs[$i]['return_time']));
		}

		return $logs;
	}

	public function store(Request $request): string
	{
		$data = $request->all()['data'];
		$bookID = $data['bookID'];
		$studentID = $data['studentID'];

		$student = Student::find($studentID);

		if ($student == NULL) {
			return "Invalid Student ID";
		}

		$approved = $student->approved;

		if ($approved == 0) {
			return "Student still not approved by Admin Librarian";
		}

		$booksIssued = $student->books_issued;
		$category = $student->category;

		$maxAllowed = StudentCategories::where('cat_id', '=', $category)->firstOrFail()->max_allowed;

		if ($booksIssued >= $maxAllowed) {
			return 'Student cannot issue any more books';
		}

		$book = Issue::where('book_id', $bookID)->where('available_status', '!=', 0)->first();

		if ($book == NULL) {
			return 'Invalid Book Issue ID';
		}

		$bookAvailability = $book->available_status;
		// dd($book);
		if ($bookAvailability != 1) {
			return 'Book not available for issue';
		}

		// book is to be issued
		DB::transaction(function () use ($bookID, $studentID) {
			$log = new Logs;

			$log->book_issue_id = $bookID;
			$log->student_id	= $studentID;
			$log->issue_by		= Auth::id();
			$log->issued_at		= date('Y-m-d H:i');
			$log->return_time	= 0;

			$log->save();

			$book = Issue::where('book_id', $bookID)->where('available_status', '!=', 0)->first();
			// changing the availability status
			$bookIssueUpdate = Issue::where('book_id', $bookID)->where('issue_id', $book->issue_id)->first();
			$bookIssueUpdate->available_status = 0;
			$bookIssueUpdate->save();

			// increasing number of books issed by student
			$student = Student::find($studentID);
			$student->books_issued = $student->books_issued + 1;
			$student->save();
		});

		return 'Book Issued Successfully!';
	}

	public function edit(int $id): string
	{
		$issueID = $id;

		$conditions = array(
			'book_issue_id'	=> $issueID,
			'return_time'	=> 0
		);

		$log = Logs::where($conditions);

		if (!$log->count()) {
			return 'Invalid Book ID entered or book already returned';
		}

		$log = Logs::where($conditions)
			->firstOrFail();


		$logId = $log['id'];
		$studentId = $log['student_id'];
		$issueId = $log['book_issue_id'];

		DB::transaction(function () use ($logId, $studentId, $issueId) {
			// change log status by changing return time
			$logChange = Logs::find($logId);
			$logChange->return_time = date('Y-m-d H:i');
			$logChange->save();

			// decrease student book issue counter
			$student = Student::find($studentId);
			$student->books_issued = $student->books_issued - 1;
			$student->save();

			// change issue availability status
			$issue = Issue::find($issueId);
			$issue->available_status = 1;
			$issue->save();
		});

		return 'Successfully returned';
	}

	public function renderLogs(): View|Factory
	{
		return view('panel.logs');
	}

	public function renderIssueReturn(): View|Factory
	{
		return view('panel.issue-return');
	}
}
