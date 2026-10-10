<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['is_published' => 'boolean', 'published_at' => 'datetime'];

    public function scopePublished($query)
    {
        return $query->where('is_published', true)->orderByDesc('published_at');
    }

    public function coverUrl(): ?string
    {
        if (! $this->cover_url) {
            return null;
        }

        return str_starts_with($this->cover_url, 'http') ? $this->cover_url : asset(ltrim($this->cover_url, '/'));
    }

    /** Very small Markdown subset (## / ### headings, - lists, paragraphs), always escaped. */
    public function renderedContent(): string
    {
        $html = '';
        $inList = false;
        foreach (preg_split('/\R/', (string) $this->content) as $line) {
            $t = trim($line);
            $isItem = str_starts_with($t, '- ') || str_starts_with($t, '* ');
            if ($inList && ! $isItem) {
                $html .= '</ul>';
                $inList = false;
            }
            if ($t === '') {
                continue;
            }
            if (str_starts_with($t, '### ')) {
                $html .= '<h3>'.e(substr($t, 4)).'</h3>';
            } elseif (str_starts_with($t, '## ')) {
                $html .= '<h2>'.e(substr($t, 3)).'</h2>';
            } elseif ($isItem) {
                if (! $inList) {
                    $html .= '<ul>';
                    $inList = true;
                }
                $html .= '<li>'.e(substr($t, 2)).'</li>';
            } else {
                $html .= '<p>'.e($t).'</p>';
            }
        }

        return $html.($inList ? '</ul>' : '');
    }
}
