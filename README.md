# Эвристический анализ текста на предмет выявления тэгов (автотэггирование текста)

Исходные тэги должны быть массивом

Алгоритм:

- удаляет у тэгов суффиксы/окончания (стемминг Портера)

- проверяет наличие в тексте полученных стем (существование)

- меряет расстояние Левенштейна между существующим тэгом и
словами исходного текста

Пример использования:

    $text = "Старика Ивана Петровича разбудил шум";
    $tags = ["Иван Петрович", "парень", "сТаРиК"]

    $analyzer = new heuristicAnalyzer($tags, $text);
    $found = $analyzer->analyze();

    var_dump($found);
    // результат будет: Иван Петрович, сТаРиК

PHP version 5.4

@author   Vladimir Chmil <vladimir.chmil@gmail.com>
