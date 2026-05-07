<?php
namespace Veneridze\LaravelDelayedReport\Data;

use App\Models\User;
use Veneridze\LaravelForms\Form;
use Veneridze\LaravelForms\RelationData;
use Spatie\LaravelData\Attributes\Hidden;
use Spatie\LaravelData\Attributes\Computed;
use Veneridze\LaravelDelayedReport\Models\Report;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\Prohibited;
use Spatie\LaravelData\Attributes\Validation\ProhibitedIf;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class ReportData extends Form
{
    #[Computed]
    readonly RelationData $user;
    #[Computed]
    readonly mixed $docs;
    #[Computed]
    readonly string $label;
    #[Computed]
    readonly string $filetype;
    #[Computed]
    readonly string $description;

    //        //#[Required]
        //#[AcceptedIf('later', true)]
        //#[RequiredIf('letterself', false)] //#[Hidden]
        //#[Prohibited]
        
        //#[Hidden]
        //public bool $later,
        //#[Hidden]
        //public bool $letterself,


    public function __construct(
        
        #[Required]
        public string $execute_at,
        #[Hidden]
        public mixed $letterself,
        public mixed $type,
        public ?array $payload,
        #[Prohibited]
        public ?int $id,
        #[Prohibited]
        public ?int $user_id,
        #[ProhibitedIf('letterself', true)]
        public ?string $email,
        #[Prohibited]
        public ?int $completed,
        #[Prohibited]
        public ?string $created_at

        //#[Prohibits('email')]
    ) {
        if ($this->id) {
            $report = Report::findOrFail($this->id);
            $this->user = RelationData::from(User::findOrFail($this->user_id));
            $this->docs = MediaData::collect($report->getMedia('result'));
            $this->label = $report->type::label();
            $this->filetype = $report->type::filetype();
            $this->description = $report->type::description($report->payload);
        }
    }
    public static string $model = Report::class;
    public static function rules(?ValidationContext $context = null)
    {
        //
        return [
            "email" => ['nullable', 'email:rfc,dns', 'required_if:letterself,0'],
            //"type" => ['required', 'string', 'max:30'],
            "payload" => ['nullable', 'array'],
            "execute_at" => ['date', 'after:now']
        ];
    }
    public static function fields(...$args): array
    {
        return [

            ...$type::fields(),
            ...[
                [
                    [
                        "type" => "radio",
                        "label" => "Создание отчёта",
                        "style" => 'button',
                        "key" => 'later',
                        "options" => [
                            [
                                "label" => "Текущий",
                                "value" => 0
                            ],
                            [
                                "label" => "Отложенный",
                                "value" => 1
                            ]
                        ]
                    ],
                    [
                        "type" => "datetime",
                        "label" => "Дата и время",
                        "key" => 'execute_at',
                        "visibleif" => [
                            'later' => 1
                        ]
                    ],
                ],
                [
                    [
                        "type" => "radio",
                        "label" => "Отправить на почту",
                        "style" => 'button',
                        "key" => 'letterself',
                        "visibleif" => [
                            'later' => 1
                        ],
                        "options" => [
                            [
                                "label" => "Себе",
                                "value" => 1
                            ],
                            [
                                "label" => "Другому",
                                "value" => 0
                            ]
                        ]
                    ],
                    [
                        "type" => "text",
                        "label" => "Адрес электронной почты",
                        "key" => 'email',
                        "visibleif" => [
                            'later' => 1,
                            'letterself' => 0
                        ]
                    ],
                ],
                [
                    [
                        "type" => "hidden",
                        "key" => "type",
                        "initvalue" => strtolower(basename((new \ReflectionClass($type))->getShortName()))
                    ]
                ]
            ]
        ];
    }
}
