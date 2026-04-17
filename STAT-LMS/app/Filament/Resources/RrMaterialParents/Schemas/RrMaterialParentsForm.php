<?php

namespace App\Filament\Resources\RrMaterialParents\Schemas;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RrMaterialParentsForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Material Details')
                    ->components([
                        TextInput::make('id')
                            ->label('RR Material Parent ID (UUID)')
                            ->disabled()
                            ->dehydrated(false)
                            ->visibleOn(['edit', 'view'])
                            ->columnSpanFull(),

                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('abstract')
                            ->maxLength(65535)
                            ->columnSpanFull(),

                        Select::make('material_type')
                            ->options([
                                1 => 'Book',
                                2 => 'Thesis',
                                3 => 'Journal',
                                4 => 'Dissertation',
                                5 => 'Others',
                            ])
                            ->required(),

                        Select::make('access_level')
                            ->options([
                                1 => 'Open Access',
                                2 => 'Restricted Access (Faculty Only)',
                                3 => 'Confidential Access (Committee and Head Only)',
                            ])
                            ->required(),
                    ])->columns(2),

                Section::make('Authors & Metadata')
                    ->components([
                        TextInput::make('author')
                            ->label('Author')
                            ->columnSpanFull()
                            ->datalist(User::all()->pluck('name')->toArray())
                            ->required(),

                        TagsInput::make('adviser')
                            ->label('Adviser(s)')
                            ->placeholder('Type name and press Enter')
                            ->columnSpanFull()
                            ->suggestions(fn () => User::pluck('name')->toArray())
                            ->required()
                            // This ensures it stores the data as a clean array in your JSON column
                            ->nestedRecursiveRules([
                                'string',
                                'max:255',
                            ]),

                        TagsInput::make('keywords')
                            ->label('Keywords')
                            ->placeholder('Type keyword and press Enter')
                            ->columnSpanFull()
                            ->required()
                            ->suggestions([
                                'Regression Analysis',
                                'Hypothesis Testing',
                                'Multivariate Analysis',
                                'Time Series Analysis',
                                'Bayesian Inference',
                                'Sampling Design',
                                'Experimental Design',
                                'Non-parametric Statistics',
                                'Survival Analysis',
                                'Machine Learning',
                                'Categorical Data Analysis',
                                'Spatial Statistics',
                                'Biostatistics',
                                'Econometrics',
                                'Quality Control',
                                'Probability Theory',
                                'Stochastic Processes',
                                'Data Mining',
                                'Statistical Modeling',
                                'Estimation Theory',
                                'Analysis of Variance (ANOVA)',
                                'Correlation Analysis',
                                'Longitudinal Data Analysis',
                                'Principal Component Analysis (PCA)',
                                'Survey Methodology',
                            ])
                            ->nestedRecursiveRules([
                                'string',
                                'max:255',
                            ]),

                        TagsInput::make('sdgs')
                            ->label('SDGs (Sustainable Development Goals)')
                            ->placeholder('Type SDG and press Enter')
                            ->columnSpanFull()
                            ->suggestions([
                                'No Poverty',
                                'Zero Hunger',
                                'Good Health and Well-being',
                                'Quality Education',
                                'Gender Equality',
                                'Clean Water and Sanitation',
                                'Affordable and Clean Energy',
                                'Decent Work and Economic Growth',
                                'Industry, Innovation and Infrastructure',
                                'Reduced Inequality',
                                'Sustainable Cities and Communities',
                                'Responsible Consumption and Production',
                                'Climate Action',
                                'Life Below Water',
                                'Life on Land',
                                'Peace, Justice and Strong Institutions',
                                'Partnerships for the Goals',
                            ])
                            ->required()
                            ->nestedRecursiveRules([
                                'string',
                                'max:255',
                            ]),

                        DatePicker::make('publication_date')
                            ->rules(['date', 'before_or_equal:today'])
                            ->required(),
                    ])->columns(2),
            ]);
    }
}
