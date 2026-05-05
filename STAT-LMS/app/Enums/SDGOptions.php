<?php

namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum SDGOptions: string implements HasLabel
{
    case SDG1 = 'No Poverty';
    case SDG2 = 'Zero Hunger';
    case SDG3 = 'Good Health and Well-being';
    case SDG4 = 'Quality Education';
    case SDG5 = 'Gender Equality';
    case SDG6 = 'Clean Water and Sanitation';
    case SDG7 = 'Affordable and Clean Energy';
    case SDG8 = 'Decent Work and Economic Growth';
    case SDG9 = 'Industry, Innovation and Infrastructure';
    case SDG10 = 'Reduced Inequality';
    case SDG11 = 'Sustainable Cities and Communities';
    case SDG12 = 'Responsible Consumption and Production';
    case SDG13 = 'Climate Action';
    case SDG14 = 'Life Below Water';
    case SDG15 = 'Life on Land';
    case SDG16 = 'Peace, Justice and Strong Institutions';
    case SDG17 = 'Partnerships for the Goals';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::SDG1 => 'No Poverty',
            self::SDG2 => 'Zero Hunger',
            self::SDG3 => 'Good Health and Well-being',
            self::SDG4 => 'Quality Education',
            self::SDG5 => 'Gender Equality',
            self::SDG6 => 'Clean Water and Sanitation',
            self::SDG7 => 'Affordable and Clean Energy',
            self::SDG8 => 'Decent Work and Economic Growth',
            self::SDG9 => 'Industry, Innovation and Infrastructure',
            self::SDG10 => 'Reduced Inequality',
            self::SDG11 => 'Sustainable Cities and Communities',
            self::SDG12 => 'Responsible Consumption and Production',
            self::SDG13 => 'Climate Action',
            self::SDG14 => 'Life Below Water',
            self::SDG15 => 'Life on Land',
            self::SDG16 => 'Peace, Justice and Strong Institutions',
            self::SDG17 => 'Partnerships for the Goals',
        };
    }
}
