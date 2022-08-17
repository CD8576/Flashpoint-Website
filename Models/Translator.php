<?php


namespace Flashpoint\Models;

class Translator
{

    private const TRANSLATION_FILE_PATH = 'locales/{locale}.json';
    private static array $dictionary = array();

    /**
     * Method loading all required translations into a static variable
     * @param string $languageCode Code of the language whose translations are needed
     * @param array $dictionaries Filenames (without extensions) of the dictionary files containing the translations
     * @return bool TRUE, if the loaded static dictionary variable is not empty after loading everything, FALSE otherwise
     */
    public static function loadDictionaries(string $languageCode, array $dictionaries): bool
    {
        foreach ($dictionaries as $dictionary) {
            self::$dictionary = array_merge(
                self::$dictionary,
                json_decode(file_get_contents('locales/'.$languageCode.'/'.$dictionary.'.json'), true)
            );
        }
        return empty(self::$dictionary);
    }

    /**
     * Method reading translation of a specific key from the loaded dictionary and returning it
     * @param string $key Key, under which the translation is saved in the translation file
     * @return string|null The translated string, or NULL, if such key isn't present in the translation file
     */
    public static function translate(string $key, string ...$formatTags): ?string
    {
        $markedString = @self::$dictionary[$key];
        if (empty($markedString)) {
            return null;
        }

        if (empty($formatTags)) {
            //Unformatted string
            return $markedString;
        }

        //Formatted string
        return self::format($markedString, ...$formatTags);
    }

    private static function format(string $markedString, string ...$formatTags) : string
    {
        $finalString = '';
        $openedFormatTagsStack = array();

        for ($i = 0; $i < mb_strlen($markedString); $i++) {
            switch (mb_substr($markedString, $i, 1)) {
                case '{':
                    $currentFormatTag = array_shift($formatTags);
                    if (!is_null($currentFormatTag)) {
                        array_push($openedFormatTagsStack, $currentFormatTag);
                        $finalString .= $currentFormatTag;
                    }
                    break;
                case '}':
                    $currentFormatTag = array_pop($openedFormatTagsStack);
                    if (!is_null($currentFormatTag)) {
                        $finalString .= self::createClosingTag($currentFormatTag);
                    }
                    break;
                default:
                    $finalString .= mb_substr($markedString, $i, 1);
            }
        }

        return $finalString;
    }

    private static function createClosingTag(string $openingTag) : string
    {
        //Replace "<" with "</"
        $openingTag = mb_substr($openingTag, 1);
        $closingTag = '</';

        //Find the first space (end of the tag name)
        $firstSpacePos = mb_strpos($openingTag, ' ');

        //No space (= no attributes) --> cut of just the last ">" char
        if ($firstSpacePos === false) {
            $firstSpacePos = mb_strlen($openingTag) - 1;
        }

        //Append the tag name
        $closingTag .= mb_substr($openingTag, 0, $firstSpacePos);

        //Append ">"
        $closingTag .= ">";

        return $closingTag;
    }
}