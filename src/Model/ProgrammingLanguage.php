<?php

namespace App\Model;

class ProgrammingLanguage
{
    public const PYTHON = 'python';
    public const JAVA = 'java';
    public const JAVASCRIPT = 'javascript';
    public const C_SHARP = 'c#';
    public const C = 'c';
    public const CPP = 'c++';
    public const PHP = 'php';
    public const R = 'r';
    public const TYPESCRIPT = 'typescript';
    public const OBJECTIVE_C = 'objective-c';
    public const SWIFT = 'swift';
    public const MATLAB = 'matlab';
    public const KOTLIN = 'kotlin';
    public const GO = 'go';
    public const RUST = 'rust';
    public const RUBY = 'ruby';
    public const VBA = 'vba';
    public const ADA = 'ada';
    public const SCALA = 'scala';
    public const VISUAL_BASIC = 'visual basic';
    public const DART = 'dart';
    public const ABAP = 'abap';
    public const LUA = 'lua';
    public const GROOVY = 'groovy';
    public const PERL = 'perl';
    public const JULIA = 'julia';

    public static function asArray(): array {
        return [
            self::PYTHON,
            self::JAVA,
            self::JAVASCRIPT,
            self::C_SHARP,
            self::C,
            self::CPP,
            self::PHP,
            self::R,
            self::TYPESCRIPT,
            self::OBJECTIVE_C,
            self::SWIFT,
            self::MATLAB,
            self::KOTLIN,
            self::GO,
            self::RUST,
            self::RUBY,
            self::VBA,
            self::ADA,
            self::SCALA,
            self::VISUAL_BASIC,
            self::DART,
            self::ABAP,
            self::LUA,
            self::GROOVY,
            self::PERL,
            self::JULIA,
        ];
    }

//ABAP (abap)
//Ada (ada,adb,ads,pad)
//ASP (asa,asp)
//ASP.NET (asax,ascx,asmx,aspx,master,sitemap,webinfo)
//Basic (bas)
//C (c,ec,pgc)
//C Header (h)
//C Shell (csh,.cshrc)
//C# (cs,csx)
//C++ (cc,cpp,cxx,c++,pcc)
//C++ Header (hh,hpp,hxx,inl,ipp)
//COBOL (cob,cbl,ccp,cobol,cpy)
//CoffeeScript (coffee)
//Dart (dart)
//DM (dm)
//Elixir (ex,exs)
//Elm (elm)
//Go (go)
//Go Template (tmpl,gohtml,gotxt)
//Haskell (hs)
//Java (java)
//JavaScript (js,mjs)
//JSX (jsx)
//Julia (jl)
//Kotlin (kt,kts)
//Lua (lua)
//Objective C (m)
//Pascal (pas)
//Perl (pl,pm)
//PHP (php)
//Python (py)
//R (r)
//Ruby (rb)
//Rust (rs)
//Swift (swift)
//TypeScript (ts,tsx)

}
