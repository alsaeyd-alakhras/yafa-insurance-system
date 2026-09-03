<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RadiologyExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('radiology_exams')->insert([
            ['category' => 'ألتراساوند وإيكو ودوبلر', 'name' => 'تصوير تلفزيون للبطن والحوض', 'price' => 20, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'ألتراساوند وإيكو ودوبلر', 'name' => 'تصوير تلفزيون للرقبة', 'price' => 20, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'ألتراساوند وإيكو ودوبلر', 'name' => 'تصوير تلفزيون للمفاصل', 'price' => 20, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'ألتراساوند وإيكو ودوبلر', 'name' => 'تصوير تلفزيون للثدي', 'price' => 20, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'ألتراساوند وإيكو ودوبلر', 'name' => 'تصوير دوبلر للأوردة', 'price' => 50, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'ألتراساوند وإيكو ودوبلر', 'name' => 'تصوير دوبلر للشرايين', 'price' => 70, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'ألتراساوند وإيكو ودوبلر', 'name' => 'تصوير دوبلر للرقبة', 'price' => 80, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'ألتراساوند وإيكو ودوبلر', 'name' => 'أخذ عينة NFA', 'price' => 250, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'ألتراساوند وإيكو ودوبلر', 'name' => 'أخذ عينة من الكبد', 'price' => 1000, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'ألتراساوند وإيكو ودوبلر', 'name' => 'صورة ايكو للقلب للكبار والصغار', 'price' => 50, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'ألتراساوند وإيكو ودوبلر', 'name' => 'عينة من الثدي', 'price' => 350, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'ألتراساوند وإيكو ودوبلر', 'name' => 'عينة مزدوجة (ثدي + خلايا لمفاوية)', 'price' => 400, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'ألتراساوند وإيكو ودوبلر', 'name' => 'True Cut BioPsy', 'price' => 500, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'CT بدون صبغة', 'name' => 'CT Brain (المخ)', 'price' => 70, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'CT بدون صبغة', 'name' => 'CT Chest H.R (صدر)', 'price' => 70, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'CT بدون صبغة', 'name' => 'CT L. Spine (الفقرات القطنية)', 'price' => 70, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'CT بدون صبغة', 'name' => 'CT Pelvis (الحوض)', 'price' => 70, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'CT بدون صبغة', 'name' => 'CT C.S (فقرات الرقبة)', 'price' => 70, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'CT بدون صبغة', 'name' => 'CT Dorsal (فقرات الصدر)', 'price' => 70, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'CT بدون صبغة', 'name' => 'CT Extremities (الأطراف)', 'price' => 70, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'CT بدون صبغة', 'name' => 'CT CTU (المسالك - الجهاز البولي)', 'price' => 70, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'CT بدون صبغة', 'name' => 'CT Topography (مسح الأطراف والحوض)', 'price' => 40, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'CT بصبغة', 'name' => 'CT Brain with contrast (المخ)', 'price' => 150, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'CT بصبغة', 'name' => 'CT Orbit with contrast (العيون)', 'price' => 150, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'CT بصبغة', 'name' => 'CT Neck with contrast (الرقبة)', 'price' => 150, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'CT بصبغة', 'name' => 'CT Chest routine with contrast (صدر)', 'price' => 150, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'CT بصبغة', 'name' => 'CT Urography with contrast', 'price' => 170, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'CT بصبغة', 'name' => 'CT Abdomen & Pelvis with contrast (البطن والحوض)', 'price' => 200, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'CT بصبغة', 'name' => 'CT Colongraphy (فحص القولون)', 'price' => 200, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'CT بصبغة', 'name' => 'CT Entereography (الأمعاء)', 'price' => 200, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'CT بصبغة', 'name' => 'CT Chest & Abd & Pelvis with contrast (الصدر والبطن والحوض)', 'price' => 250, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'بانوراما الأسنان', 'name' => 'بانوراما أسنان', 'price' => 10, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'بانوراما الأسنان', 'name' => 'Cephalometric X-ray', 'price' => 10, 'discount_amount' => 0, 'is_active' => true],
            ['category' => 'أشعة عادية', 'name' => 'الفحص الواحد', 'price' => 20, 'discount_amount' => 0, 'is_active' => true],
        ]);
    }
}
