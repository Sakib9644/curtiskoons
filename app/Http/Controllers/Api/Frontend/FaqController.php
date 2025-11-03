<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\FAQ;

class FaqController extends Controller
{
    public function index()
    {
        $faq = FAQ::select('question', 'answer')->get()->map(function ($item) {
            // Remove HTML tags from answer
            $item->answer = strip_tags($item->answer);

            // Remove numbers and dots from beginning of question (like "01." or "02. ")
            $item->question = preg_replace('/^\d+\.\s*/', '', $item->question);

            return $item;
        });

        return Helper::jsonResponse(true, 'FAQ list', 200, $faq);
    }
}
