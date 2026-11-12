<?php

namespace Database\Seeders;

use App\Models\Pms\PmsCategory;
use App\Models\Pms\PmsCycle;
use App\Models\Pms\PmsKpiItem;
use App\Models\Pms\PmsRole;
use Illuminate\Database\Seeder;

class PmsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = $this->templates();

        foreach ($templates as $roleData) {
            $role = PmsRole::query()->updateOrCreate(
                ['code' => $roleData['code']],
                [
                    'name' => $roleData['name'],
                    'description' => $roleData['description'],
                ]
            );

            foreach ($roleData['categories'] as $categoryIndex => $categoryData) {
                $category = PmsCategory::query()->updateOrCreate(
                    [
                        'pms_role_id' => $role->id,
                        'name' => $categoryData['name'],
                    ],
                    [
                        'default_weight' => $categoryData['weight'],
                        'sort_order' => $categoryIndex + 1,
                    ]
                );

                $itemCount = max(count($categoryData['items']), 1);
                $evenWeight = round($categoryData['weight'] / $itemCount, 2);
                $assigned = 0;

                foreach ($categoryData['items'] as $itemIndex => $item) {
                    $isLast = $itemIndex === $itemCount - 1;
                    $weight = $isLast
                        ? round($categoryData['weight'] - $assigned, 2)
                        : $evenWeight;
                    $assigned += $evenWeight;

                    PmsKpiItem::query()->updateOrCreate(
                        [
                            'pms_category_id' => $category->id,
                            'name' => $item['name'],
                        ],
                        [
                            'description' => $item['description'],
                            'default_weight' => $weight,
                            'default_target' => $item['target'],
                            'sort_order' => $itemIndex + 1,
                        ]
                    );
                }
            }
        }

        PmsCycle::query()->updateOrCreate(
            ['year' => (int) now()->year],
            [
                'title' => now()->year.' Performance Cycle',
                'status' => 'active',
                'h1_opens_at' => now()->year.'-01-01',
                'h1_closes_at' => now()->year.'-06-30',
                'h2_opens_at' => now()->year.'-07-01',
                'h2_closes_at' => now()->year.'-12-31',
                'notes' => 'Seeded from Branch Operations Staff KPI framework.',
            ]
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function templates(): array
    {
        return [
            [
                'code' => 'BOE',
                'name' => 'Bank Operation Executive',
                'description' => 'Branch operations leadership KPIs',
                'categories' => [
                    [
                        'name' => 'Financial',
                        'weight' => 30,
                        'items' => [
                            ['name' => 'Deposit mobilization', 'description' => 'Mobilize deposits as agreed for the branch.', 'target' => '120mil per year'],
                            ['name' => 'Dormant account', 'description' => 'Dormant accounts should be less than 5% of all branch customer accounts.', 'target' => '< 5% dormant'],
                            ['name' => 'Digital Services', 'description' => 'Ensure 80% of branch customers are registered on digital channels.', 'target' => '80% digital adoption'],
                            ['name' => 'Prevent fees & commissions leakages', 'description' => 'Verify all fees are recovered as per tariff guide.', 'target' => '100% compliance'],
                        ],
                    ],
                    [
                        'name' => 'Customers',
                        'weight' => 25,
                        'items' => [
                            ['name' => 'Quality of service', 'description' => 'Verify requests before processing; close CRM complaints on time; support agent banking.', 'target' => '100% compliance'],
                            ['name' => 'Errors / Complaints', 'description' => 'No misposting/duplication; reduce customer complaints by 80% vs last year.', 'target' => '80% complaint reduction'],
                        ],
                    ],
                    [
                        'name' => 'Quality of Processes',
                        'weight' => 40,
                        'items' => [
                            ['name' => 'KYC completeness & reconciliations', 'description' => 'Complete KYC for account opening, TT, and maintenance; clear suspense entries.', 'target' => '100% compliance'],
                            ['name' => 'Repeated audit issues', 'description' => 'No repeated or overdue audit issues.', 'target' => 'Zero repeated issues'],
                            ['name' => 'Prevent fraudulent incidents', 'description' => 'Ensure no fraud incidents.', 'target' => 'Zero fraud'],
                            ['name' => 'Submission of self assessment', 'description' => 'Conduct and submit self-assessments on time and close gaps.', 'target' => 'On-time submission'],
                        ],
                    ],
                    [
                        'name' => 'People (Learning & Growth)',
                        'weight' => 5,
                        'items' => [
                            ['name' => 'Staff management & training', 'description' => 'Attend at least two mandatory trainings and cascade policies to staff.', 'target' => '2+ trainings'],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'SUPERVISOR',
                'name' => 'Supervisor',
                'description' => 'Operations supervisor KPIs',
                'categories' => [
                    [
                        'name' => 'Financial',
                        'weight' => 30,
                        'items' => [
                            ['name' => 'Deposit mobilization', 'description' => 'Mobilize deposits as agreed.', 'target' => '60mil per year'],
                            ['name' => 'Dormant account', 'description' => 'Keep dormant accounts below 5%.', 'target' => '< 5% dormant'],
                            ['name' => 'Digital Services', 'description' => 'Drive digital channel registration.', 'target' => '80% digital adoption'],
                            ['name' => 'Prevent fees & commissions leakages', 'description' => 'No fee leakages.', 'target' => '100% compliance'],
                        ],
                    ],
                    [
                        'name' => 'Customers',
                        'weight' => 25,
                        'items' => [
                            ['name' => 'Quality of service', 'description' => 'Verify requests, close complaints, support agent banking.', 'target' => '100% compliance'],
                            ['name' => 'Errors / Complaints', 'description' => 'No misposting; reduce complaints.', 'target' => '80% complaint reduction'],
                        ],
                    ],
                    [
                        'name' => 'Quality of Processes',
                        'weight' => 40,
                        'items' => [
                            ['name' => 'KYC completeness & reconciliations', 'description' => 'KYC completeness and suspense follow-up.', 'target' => '100% compliance'],
                            ['name' => 'Repeated audit issues', 'description' => 'No repeated/overdue audit issues.', 'target' => 'Zero repeated issues'],
                            ['name' => 'Prevent fraudulent incidents', 'description' => 'Ensure no fraud incidents.', 'target' => 'Zero fraud'],
                        ],
                    ],
                    [
                        'name' => 'People (Learning & Growth)',
                        'weight' => 5,
                        'items' => [
                            ['name' => 'Staff management & training', 'description' => 'Attend trainings and cascade knowledge.', 'target' => '2+ trainings'],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'CSO',
                'name' => 'Customer Service Officer',
                'description' => 'Front-office customer service KPIs',
                'categories' => [
                    [
                        'name' => 'Financial',
                        'weight' => 25,
                        'items' => [
                            ['name' => 'Deposit mobilization', 'description' => 'Mobilize deposits.', 'target' => '120mil per year'],
                            ['name' => 'Digital Services', 'description' => 'Register customers on digital channels.', 'target' => '80% digital adoption'],
                            ['name' => 'Prevent fees & commissions leakages', 'description' => 'No fee leakages.', 'target' => '100% compliance'],
                        ],
                    ],
                    [
                        'name' => 'Customers',
                        'weight' => 25,
                        'items' => [
                            ['name' => 'Quality of service', 'description' => 'Verify requests; close CRM complaints; apply tariff correctly.', 'target' => '100% compliance'],
                            ['name' => 'Errors / Complaints', 'description' => 'No misposting; reduce complaints.', 'target' => '80% complaint reduction'],
                        ],
                    ],
                    [
                        'name' => 'Quality of Processes',
                        'weight' => 45,
                        'items' => [
                            ['name' => 'KYC completeness & data capturing', 'description' => 'Complete KYC for account opening, TT, FD, maintenance.', 'target' => '100% compliance'],
                            ['name' => 'Repeated audit issues', 'description' => 'No repeated/outstanding audit issues.', 'target' => 'Zero repeated issues'],
                            ['name' => 'Prevent fraudulent incidents', 'description' => 'Ensure no fraud incidents.', 'target' => 'Zero fraud'],
                        ],
                    ],
                    [
                        'name' => 'People (Learning & Growth)',
                        'weight' => 5,
                        'items' => [
                            ['name' => 'Learning & attestation', 'description' => 'Attend trainings and sign policy attestation.', 'target' => '2+ trainings + attestation'],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'BOO',
                'name' => 'Bank Operations Officer',
                'description' => 'Back-office operations KPIs',
                'categories' => [
                    [
                        'name' => 'Financial',
                        'weight' => 25,
                        'items' => [
                            ['name' => 'Deposit mobilization', 'description' => 'Mobilize deposits.', 'target' => '60mil per year'],
                            ['name' => 'Prevent fees & commissions leakages', 'description' => 'No fee leakages.', 'target' => '100% compliance'],
                        ],
                    ],
                    [
                        'name' => 'Customers',
                        'weight' => 25,
                        'items' => [
                            ['name' => 'Quality of service', 'description' => 'Verify requests; close complaints; apply tariff correctly.', 'target' => '100% compliance'],
                            ['name' => 'Errors / Complaints', 'description' => 'No misposting; reduce complaints.', 'target' => '80% complaint reduction'],
                        ],
                    ],
                    [
                        'name' => 'Quality of Processes',
                        'weight' => 45,
                        'items' => [
                            ['name' => 'KYC, posting accuracy & reconciliations', 'description' => 'KYC completeness, correct posting, and no long outstanding suspense.', 'target' => '100% compliance'],
                            ['name' => 'Repeated audit issues', 'description' => 'No repeated/outstanding audit issues.', 'target' => 'Zero repeated issues'],
                            ['name' => 'Prevent fraudulent incidents', 'description' => 'Ensure no fraud incidents.', 'target' => 'Zero fraud'],
                        ],
                    ],
                    [
                        'name' => 'People (Learning & Growth)',
                        'weight' => 5,
                        'items' => [
                            ['name' => 'Learning & attestation', 'description' => 'Attend trainings and sign policy attestation.', 'target' => '2+ trainings + attestation'],
                        ],
                    ],
                ],
            ],
            [
                'code' => 'TELLER',
                'name' => 'Teller',
                'description' => 'Cash counter operations KPIs',
                'categories' => [
                    [
                        'name' => 'Financial',
                        'weight' => 25,
                        'items' => [
                            ['name' => 'Deposit mobilization', 'description' => 'Mobilize deposits.', 'target' => '60mil per year'],
                            ['name' => 'Prevent fees & commissions leakages', 'description' => 'No fee leakages.', 'target' => '100% compliance'],
                        ],
                    ],
                    [
                        'name' => 'Customers',
                        'weight' => 25,
                        'items' => [
                            ['name' => 'Quality of service', 'description' => 'Verify requests and apply tariff correctly.', 'target' => '100% compliance'],
                            ['name' => 'Errors / Complaints', 'description' => 'No misposting; reduce complaints.', 'target' => '80% complaint reduction'],
                        ],
                    ],
                    [
                        'name' => 'Quality of Processes',
                        'weight' => 45,
                        'items' => [
                            ['name' => 'KYC, cash controls & accuracy', 'description' => 'KYC completeness, correct posting, and zero cash overage/shortage.', 'target' => '100% compliance'],
                            ['name' => 'Repeated audit issues', 'description' => 'No repeated/outstanding audit issues.', 'target' => 'Zero repeated issues'],
                            ['name' => 'Prevent fraudulent incidents', 'description' => 'Ensure no fraud incidents.', 'target' => 'Zero fraud'],
                        ],
                    ],
                    [
                        'name' => 'People (Learning & Growth)',
                        'weight' => 5,
                        'items' => [
                            ['name' => 'Learning & attestation', 'description' => 'Attend trainings and sign policy attestation.', 'target' => '2+ trainings + attestation'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
