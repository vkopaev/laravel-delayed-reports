<?php
namespace Veneridze\LaravelDelayedReport\Controllers;


use Validator;
use Illuminate\Http\Request;
use Axiom\Rules\DisposableEmail;
use Illuminate\Routing\Controller;
use Veneridze\ModelTypes\TypeCollection;
use Veneridze\LaravelDelayedReport\Models\Report;
use Veneridze\LaravelDelayedReport\Data\ReportData;

class ReportController extends Controller
{

    /**
     * Show the form for creating a new resource.
     */

    /**
     * Store a newly created resource in storage.
     */
    public function store(ReportData $request)
    {
        $request->user_id = auth()->user()->id;
        //$type = array_values(array_filter(config('type.report'), fn(string $type): bool => basename($type) == $request->type))[0];
        $request_type = $request->type;
        $type = (new TypeCollection('report'))->$request_type;
        //$request->validate($type::rules());
        $valide = Validator::make($request->all(), $type::rules());
        if ($valide->fails()) {
            return back()->withErrors($valide->errors());
        }
        if ($request->letterself) {
            $request->email = auth()->user()->email;
            $valide = Validator::make([
                'letterself' => $request->email
            ], [
                'letterself' => ['required', 'email:rfc,dns', 'required_if:letterself,0']
            ], [
                'letterself' => "На вашу почту {$request->email} не может быть отправлено сообщение"
            ]);
            if ($valide->fails()) {
                return back()->withErrors($valide->errors());
            }
        }
        Report::create($request->all());
        return back();

    }

    public function download(Request $request)
    {
        $payload = $request->input('payload');
        $request_type = $request->input('type');
        $type = (new TypeCollection('report'))->$request_type;
        $valide = Validator::make([
            'payload' => $payload
        ], $type::rules());

        if ($valide->fails()) {
            return response([
                'errors' => $valide->errors()
            ], 400);
        }
        $files = $type::generate($request->all());
        return response()->download($files);

        if (is_string($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            $this->addMedia($file)->toMediaCollection('result');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Report $report)
    {
        abort_if($report->completed === 1, 400, "Нельзя удалить выполненный отчёт");
        $report->deleteOrFail();
        return back();
    }
}
