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
                'title' => 'An Introduction to Mathematical Statistics',
                'abstract' => 'A comprehensive textbook providing foundational knowledge in mathematical statistics and statistical inference, covering probability theory, estimation, and hypothesis testing.',
                'keywords' => ['mathematical statistics', 'probability theory', 'statistical inference', 'estimation', 'hypothesis testing'],
                'sdgs' => ['Quality Education'],
                'publication_date' => '2016-01-01',
                'author' => 'Fetsje Bijma, Marianne Jonker, Aad van der Vaart',
                'adviser' => [],
                'access_level' => 1,
            ],

            [
                'id' => self::PSA_JOURNAL_ID,
                'material_type' => 2, // Journal
                'title' => 'Statistics Education Research in Malaysia and the Philippines: A Comparative Analysis',
                'abstract' => 'This paper presents a comparative analysis of statistics education research in Malaysia and the Philippines by modes of dissemination, research areas, and trends.',
                'keywords' => ['statistics education', 'comparative analysis', 'Malaysia', 'Philippines', 'research trends'],
                'sdgs' => ['Quality Education'],
                'publication_date' => '2014-01-01',
                'author' => 'Enriqueta Reston, Saras Krishnan, Noraini Idris',
                'adviser' => [],
                'access_level' => 1,
            ],

            [
                'id' => self::APPLIED_STATS_BOOK_ID,
                'material_type' => 2, // Journal
                'title' => 'An Insight in Statistical Techniques and Design in Agricultural and Applied Research',
                'abstract' => 'Advance applied science researches have experienced a dramatic change in knowledge and an exponential increase in technology. The goal of applied research is to provide data to support existing knowledge by filling information gaps in applied statistics.',
                'keywords' => ['statistical techniques', 'agricultural research', 'applied research', 'experimental design', 'statistical analysis'],
                'sdgs' => ['Zero Hunger', 'Industry, Innovation and Infrastructure'],
                'publication_date' => '2012-01-01',
                'author' => 'Ajay S. Singh, Micah B. Masuku',
                'adviser' => [],
                'access_level' => 1,
            ],

            [
                'id' => self::TIMESERIES_JOURNAL_ID,
                'material_type' => 2, // Journal
                'title' => 'Modeling and prediction of COVID-19 spread in the Philippines by October 13, 2020, by using the VARMAX time series method with preventive measures',
                'abstract' => 'COVID-19 outbreak is the serious public health challenge the world is facing in recent days as there is no effective vaccine and treatment for this virus. It causes 257,863 confirmed cases as of September 13, 2020, with 4292 deaths in the Philippines.',
                'keywords' => ['COVID-19', 'VARMAX', 'time series', 'Philippines', 'SARS-CoV-2', 'pandemic', 'forecasting'],
                'sdgs' => ['Good Health and Well-being'],
                'publication_date' => '2021-01-01',
                'author' => 'Parikshit Gautam Jamdade, Shrinivas Gautamrao Jamdade',
                'adviser' => [],
                'access_level' => 1,
            ],

            // ── Restricted Materials ───────────────────────────────────────────

            [
                'id' => self::REGRESSION_THESIS_ID,
                'material_type' => 3, // Thesis
                'title' => 'Regression Analysis of Rice Yield Determinants in Luzon',
                'abstract' => 'This thesis investigates the key determinants of rice yield in Luzon using multiple linear regression and panel data methods. Data were sourced from the Philippine Statistics Authority covering 2010–2022. Results indicate that rainfall, fertilizer usage, and irrigation access are significant predictors of yield variability.',
                'keywords' => ['regression analysis', 'rice yield', 'Luzon', 'panel data', 'Philippine agriculture'],
                'sdgs' => ['Zero Hunger', 'Decent Work and Economic Growth'],
                'publication_date' => '2023-03-20',
                'author' => 'Carlos Miguel Santos',
                'adviser' => ['Dr. Jose Santos'],
                'access_level' => 2,
            ],

            [
                'id' => self::BAYESIAN_THESIS_ID,
                'material_type' => 2, // Journal
                'title' => 'Bayesian Additional Evidence for Decision Making under Small Sample Uncertainty',
                'abstract' => 'Statistical inference based on small datasets, commonly found in precision oncology, is subject to low power and high uncertainty. We developed a new method, Bayesian Additional Evidence (BAE), that determines how much additional supportive evidence is needed for a non-significant result.',
                'keywords' => ['Bayesian inference', 'small sample', 'statistical uncertainty', 'decision making', 'evidence synthesis'],
                'sdgs' => ['Good Health and Well-being'],
                'publication_date' => '2021-01-01',
                'author' => 'Arjun Sondhi, Brian Segal, Jeremy Snider, Olivier Humblet, Margaret McCusker',
                'adviser' => [],
                'access_level' => 2,
            ],

            [
                'id' => self::ML_THESIS_ID,
                'material_type' => 2, // Journal
                'title' => 'Rice Yield Modeling Using Machine Learning Algorithms Based on Environmental and Agronomic Data of Pampanga River Basin, Philippines',
                'abstract' => 'This study investigated the environmental and agronomic factors that influence rice crop yields in Pampanga River Basin in Central Luzon Philippines, specifically examining fifteen environmental and agronomic factors.',
                'keywords' => ['machine learning', 'rice yield', 'Pampanga River Basin', 'environmental factors', 'agronomic data', 'Philippines'],
                'sdgs' => ['Zero Hunger', 'Industry, Innovation and Infrastructure', 'Climate Action'],
                'publication_date' => '2023-01-01',
                'author' => 'Maria Christina V. David',
                'adviser' => [],
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
        // RrMaterialParents::factory(5)->create(['access_level' => 1, 'material_type' => fake()->randomElement([1, 3])]);
        // RrMaterialParents::factory(3)->create(['access_level' => 2, 'material_type' => 2]);
    }
}
