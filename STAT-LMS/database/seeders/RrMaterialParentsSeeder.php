<?php

namespace Database\Seeders;

use App\Models\RrMaterialParents;
use Illuminate\Database\Seeder;

class RrMaterialParentsSeeder extends Seeder
{
    /*
     * Pre-determined UUIDs
     *
     * Pattern: 22222222-2222-2222-2222-0000000000{nn}
     */
    public const STATS_BOOK_ID = '22222222-2222-2222-2222-000000000001'; // Book,        Public (1)

    public const REGRESSION_THESIS_ID = '22222222-2222-2222-2222-000000000002'; // Thesis,      Restricted (2)

    public const PSA_JOURNAL_ID = '22222222-2222-2222-2222-000000000003'; // Journal,     Public (1)

    public const MULTIVARIATE_DISS_ID = '22222222-2222-2222-2222-000000000004'; // Dissertation,Confidential (3)

    public const APPLIED_STATS_BOOK_ID = '22222222-2222-2222-2222-000000000005'; // Book,        Public (1)

    public const BAYESIAN_THESIS_ID = '22222222-2222-2222-2222-000000000006'; // Thesis,      Restricted (2)

    public const TIMESERIES_JOURNAL_ID = '22222222-2222-2222-2222-000000000007'; // Journal,     Public (1)

    public const ML_THESIS_ID = '22222222-2222-2222-2222-000000000008'; // Thesis,      Restricted (2)

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parents = [

            // ── Public Materials ───────────────────────────────────────────────

            [
                'id' => self::STATS_BOOK_ID,
                'material_type' => 1, // Book
                'title' => 'Introduction to Mathematical Statistics',
                'abstract' => 'A comprehensive introductory textbook covering probability theory, '
                    .'statistical inference, regression analysis, and hypothesis testing. Designed for '
                    .'students of the Institute of Statistics and related fields, this text bridges '
                    .'theoretical foundations with practical applications in Philippine contexts.',
                'keywords' => ['probability', 'statistical inference', 'regression', 'hypothesis testing', 'mathematical statistics'],
                'sdgs' => ['Quality Education'],
                'publication_date' => '2019-06-15',
                'author' => 'Ricardo Manuel Garcia', // matches FACULTY_1 name → resolves authorUser relation
                'adviser' => [],
                'access_level' => 1,
            ],

            [
                'id' => self::PSA_JOURNAL_ID,
                'material_type' => 3, // Journal
                'title' => 'Philippine Statistical Journal, Vol. 42 No. 1',
                'abstract' => 'The Philippine Statistical Journal is a peer-reviewed publication '
                    .'featuring research in applied statistics, official statistics methodology, and '
                    .'quantitative social science. This volume includes articles on poverty measurement, '
                    .'demographic analysis, and econometric methods applied to Philippine data.',
                'keywords' => ['Philippine statistics', 'applied statistics', 'poverty measurement', 'demography', 'econometrics'],
                'sdgs' => ['No Poverty', 'Quality Education', 'Reduced Inequality'],
                'publication_date' => '2022-01-31',
                'author' => 'PSA Editorial Board',
                'adviser' => [],
                'access_level' => 1,
            ],

            [
                'id' => self::APPLIED_STATS_BOOK_ID,
                'material_type' => 1, // Book
                'title' => 'Applied Statistical Methods for Agricultural Research',
                'abstract' => 'A practical guide to statistical methods commonly used in agricultural '
                    .'research, including experimental design, analysis of variance, regression models, and '
                    .'non-parametric methods. Each chapter includes worked examples from Philippine '
                    .'agricultural studies and exercises for self-assessment.',
                'keywords' => ['experimental design', 'ANOVA', 'non-parametric methods', 'agricultural research', 'statistical methods'],
                'sdgs' => ['Zero Hunger', 'Industry, Innovation and Infrastructure'],
                'publication_date' => '2018-11-01',
                'author' => 'Bienvenido Santos Lim',
                'adviser' => [],
                'access_level' => 1,
            ],

            [
                'id' => self::TIMESERIES_JOURNAL_ID,
                'material_type' => 3, // Journal
                'title' => 'Time Series Analysis of COVID-19 Incidence in the Philippines',
                'abstract' => 'This article presents a comprehensive time-series analysis of COVID-19 '
                    .'case counts in the Philippines using ARIMA, GARCH, and exponential smoothing models. '
                    .'Intervention analysis identifies the impact of quarantine policies on transmission '
                    .'rates. Forecasts are validated against DOH surveillance data.',
                'keywords' => ['time series', 'ARIMA', 'COVID-19', 'Philippines', 'intervention analysis'],
                'sdgs' => ['Good Health and Well-being'],
                'publication_date' => '2021-12-01',
                'author' => 'Ricardo Manuel Garcia',
                'adviser' => [],
                'access_level' => 1,
            ],

            // ── Restricted Materials ───────────────────────────────────────────

            [
                'id' => self::REGRESSION_THESIS_ID,
                'material_type' => 2, // Thesis
                'title' => 'Regression Analysis of Rice Yield Determinants in Luzon',
                'abstract' => 'This thesis investigates the key determinants of rice yield in Luzon '
                    .'using multiple linear regression and panel data methods. Data were sourced from the '
                    .'Philippine Statistics Authority covering 2010–2022. Results indicate that rainfall, '
                    .'fertilizer usage, and irrigation access are significant predictors of yield variability.',
                'keywords' => ['regression analysis', 'rice yield', 'Luzon', 'panel data', 'Philippine agriculture'],
                'sdgs' => ['Zero Hunger', 'Decent Work and Economic Growth'],
                'publication_date' => '2023-03-20',
                'author' => 'Carlos Miguel Santos', // matches STUDENT_1 name
                'adviser' => ['Ricardo Manuel Garcia'],
                'access_level' => 2,
            ],

            [
                'id' => self::BAYESIAN_THESIS_ID,
                'material_type' => 2, // Thesis
                'title' => 'Bayesian Estimation Approaches in Small-Sample Clinical Studies',
                'abstract' => 'This thesis explores Bayesian inference methods as alternatives to '
                    .'classical frequentist approaches in clinical studies with limited sample sizes. Prior '
                    .'distributions are elicited from expert clinicians, and posterior estimates are compared '
                    .'against maximum likelihood estimates across simulated and real-world datasets.',
                'keywords' => ['Bayesian inference', 'small sample', 'clinical studies', 'prior distribution', 'posterior estimation'],
                'sdgs' => ['Good Health and Well-being'],
                'publication_date' => '2022-07-10',
                'author' => 'Angelica Flores Mendoza', // matches STUDENT_2 name
                'adviser' => ['Esperanza Luz Villanueva'],
                'access_level' => 2,
            ],

            [
                'id' => self::ML_THESIS_ID,
                'material_type' => 2, // Thesis
                'title' => 'Machine Learning Applications for Agricultural Yield Prediction in the Philippines',
                'abstract' => 'This thesis benchmarks supervised machine learning algorithms—random '
                    .'forests, gradient boosting, support vector machines, and neural networks—for predicting '
                    .'crop yields using satellite imagery and meteorological data across Philippine provinces. '
                    .'Ensemble methods consistently outperform traditional linear models.',
                'keywords' => ['machine learning', 'random forest', 'crop yield prediction', 'remote sensing', 'Philippine agriculture'],
                'sdgs' => ['Zero Hunger', 'Industry, Innovation and Infrastructure', 'Climate Action'],
                'publication_date' => '2023-06-01',
                'author' => 'Rafael Jose Torres', // matches STUDENT_3 name
                'adviser' => ['Ricardo Manuel Garcia', 'Esperanza Luz Villanueva'],
                'access_level' => 2,
            ],

            // ── Confidential Materials ─────────────────────────────────────────

            [
                'id' => self::MULTIVARIATE_DISS_ID,
                'material_type' => 4, // Dissertation
                'title' => 'Multivariate Analysis of Philippine Socioeconomic Indicators: A Longitudinal Perspective',
                'abstract' => 'This dissertation employs multivariate statistical methods including '
                    .'principal component analysis, canonical correlation, and structural equation modeling '
                    .'to examine the interrelationships among key socioeconomic indicators in the Philippines '
                    .'from 1990 to 2020. Contains unpublished raw datasets from national surveys.',
                'keywords' => ['multivariate analysis', 'PCA', 'structural equation modeling', 'socioeconomic indicators', 'Philippines'],
                'sdgs' => ['No Poverty', 'Reduced Inequality', 'Decent Work and Economic Growth'],
                'publication_date' => '2021-09-15',
                'author' => 'Esperanza Luz Villanueva', // matches FACULTY_2 name
                'adviser' => ['Maria Luisa Santos Reyes'],
                'access_level' => 3,
            ],

        ];

        foreach ($parents as $data) {
            RrMaterialParents::factory()->create($data);
        }

        // ── Additional random materials ────────────────────────────────────────
        RrMaterialParents::factory(5)->create(['access_level' => 1, 'material_type' => fake()->randomElement([1, 3])]);
        RrMaterialParents::factory(3)->create(['access_level' => 2, 'material_type' => 2]);
    }
}
