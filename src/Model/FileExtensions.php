<?php

namespace App\Model;

use function implode;

class FileExtensions
{
    public const ABAP = 'abap';
    public const ADA = 'ada,adb,ads,pad';
    public const ASP = 'asa,asp';
    public const ASP_NET = 'asax,ascx,asmx,aspx,master,sitemap,webinfo';
    public const BASIC = 'bas';
    public const C = 'c,ec,pgc';
    public const C_HEADER = 'h';
    public const C_SHARP = 'cs,csx';
    public const CPP = 'cc,cpp,cxx,c++,pcc';
    public const CPP_HEADER = 'hh,hpp,hxx,inl,ipp';
    public const COBOL = 'cob,cbl,ccp,cobol,cpy';
    public const COFFEESCRIPT = 'coffee';
    public const DART = 'dart';
//    public const ELIXIR = 'ex,exs';
//    public const ELM = 'elm';
    public const GO = 'go';
    public const HASKELL = 'hs';
    public const JAVA = 'java';
    public const JAVASCRIPT = 'js,mjs';
    public const JSX = 'jsx';
    public const JULIA = 'jl';
    public const KOTLIN = 'kt,kts';
    public const LUA = 'lua';
    public const OBJECTIVE_C = 'm';
    public const PASCAL = 'pas';
    public const PERL = 'pl,pm';
    public const PHP = 'php';
    public const PYTHON = 'py';
    public const R = 'r';
    public const RUBY = 'rb';
    public const RUST = 'rs';
    public const SWIFT = 'swift';
    public const TYPESCRIPT = 'ts,tsx';
    
    public static function asArray(): array {
        return [
            self::ABAP,
            self::ADA,
//            self::ASP,
//            self::ASP_NET,
//            self::BASIC,
            self::C,
//            self::C_HEADER,
            self::C_SHARP,
            self::CPP,
//            self::CPP_HEADER,
//            self::COBOL,
            self::COFFEESCRIPT,
            self::DART,
//            self::ELIXIR,
//            self::ELM,
            self::GO,
//            self::HASKELL,
            self::JAVA,
            self::JAVASCRIPT,
//            self::JSX,
            self::JULIA,
//            self::KOTLIN,
//            self::LUA,
            self::OBJECTIVE_C,
//            self::PASCAL,
            self::PERL,
            self::PHP,
            self::PYTHON,
            self::R,
//            self::RUBY,
            self::RUST,
            self::SWIFT,
            self::TYPESCRIPT
        ];
    }

    public static function asString(): string {
        return implode(',', self::asArray());
    }
}
