<?php

namespace Database\Seeders;

use App\Models\Tour;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Usuario administrador (cambia la contraseña después de entrar)
        User::updateOrCreate(
            ['email' => 'admin@mayoviagens.com'],
            ['name' => 'Administrador', 'password' => 'Mayo2026!']
        );

        $tours = [
            [
                'nombre' => 'City Tour Cusco',
                'slug' => 'city-tour-cusco',
                'descripcion' => "Recorra a capital histórica do Império Inca: Plaza de Armas, Catedral, Qoricancha e as ruínas ao redor da cidade.\n\nUm passeio ideal para o primeiro dia, com guia em português e muita história.",
                'duracion' => 'Meio dia (4 horas)',
                'precio' => 35,
                'imagen' => 'img/tours/city-tour.jpg',
                'itinerario' => "Saída da Plaza de Armas\nCatedral de Cusco\nQoricancha\nSacsayhuamán, Q'enqo, Puka Pukara e Tambomachay",
                'incluye' => "Guia profissional em português\nTransporte turístico",
                'no_incluye' => "Ingressos\nAlimentação",
                'traducciones' => [
                    'es' => [
                        'nombre' => "City Tour Cusco",
                        'descripcion' => "Recorre la capital histórica del Imperio Inca: Plaza de Armas, Catedral, Qoricancha y las ruinas alrededor de la ciudad.\n\nUn paseo ideal para el primer día, con guía profesional y mucha historia.",
                        'duracion' => "Medio día (4 horas)",
                        'itinerario' => "Salida desde la Plaza de Armas\nCatedral del Cusco\nQoricancha\nSacsayhuamán, Q'enqo, Puka Pukara y Tambomachay",
                        'incluye' => "Guía profesional\nTransporte turístico",
                        'no_incluye' => "Entradas\nAlimentación",
                    ],
                    'en' => [
                        'nombre' => "Cusco City Tour",
                        'descripcion' => "Explore the historic capital of the Inca Empire: Plaza de Armas, Cathedral, Qoricancha and the ruins around the city.\n\nA great first-day outing, with a professional guide and plenty of history.",
                        'duracion' => "Half day (4 hours)",
                        'itinerario' => "Departure from Plaza de Armas\nCusco Cathedral\nQoricancha\nSacsayhuamán, Q'enqo, Puka Pukara and Tambomachay",
                        'incluye' => "Professional guide\nTourist transport",
                        'no_incluye' => "Entrance tickets\nMeals",
                    ],
                ],
            ],
            [
                'nombre' => 'Vale Sagrado',
                'slug' => 'vale-sagrado',
                'descripcion' => "Um dia pelos povoados, mercados e sítios arqueológicos do Vale Sagrado dos Incas: Pisac, Ollantaytambo e Chinchero.\n\nPaisagens e cultura viva em um só passeio.",
                'duracion' => 'Dia inteiro',
                'precio' => 45,
                'imagen' => 'img/tours/vale-sagrado.jpg',
                'itinerario' => "Pisac (mercado e ruínas)\nAlmoço buffet\nOllantaytambo\nChinchero",
                'incluye' => "Guia profissional em português\nTransporte\nAlmoço buffet",
                'no_incluye' => "Boleto turístico\nGorjetas",
                'traducciones' => [
                    'es' => [
                        'nombre' => "Valle Sagrado",
                        'descripcion' => "Un día por los pueblos, mercados y sitios arqueológicos del Valle Sagrado de los Incas: Pisac, Ollantaytambo y Chinchero.\n\nPaisajes y cultura viva en un solo paseo.",
                        'duracion' => "Día completo",
                        'itinerario' => "Pisac (mercado y ruinas)\nAlmuerzo buffet\nOllantaytambo\nChinchero",
                        'incluye' => "Guía profesional\nTransporte\nAlmuerzo buffet",
                        'no_incluye' => "Boleto turístico\nPropinas",
                    ],
                    'en' => [
                        'nombre' => "Sacred Valley",
                        'descripcion' => "A day through the towns, markets and archaeological sites of the Inca Sacred Valley: Pisac, Ollantaytambo and Chinchero.\n\nLandscapes and living culture in a single outing.",
                        'duracion' => "Full day",
                        'itinerario' => "Pisac (market and ruins)\nBuffet lunch\nOllantaytambo\nChinchero",
                        'incluye' => "Professional guide\nTransport\nBuffet lunch",
                        'no_incluye' => "Tourist ticket\nTips",
                    ],
                ],
            ],
            [
                'nombre' => 'Machu Picchu',
                'slug' => 'machu-picchu',
                'descripcion' => "A maravilha do mundo. Visita guiada à cidadela inca, com opções de trem e de ingresso à montanha.\n\nConsulte datas e disponibilidade: os ingressos esgotam com antecedência.",
                'duracion' => '1 ou 2 dias',
                'precio' => 290,
                'imagen' => 'img/tours/machu-picchu.jpg',
                'itinerario' => "Traslado a Ollantaytambo\nTrem para Aguas Calientes\nÔnibus até a cidadela\nVisita guiada de 2 horas",
                'incluye' => "Guia em português\nTrem de ida e volta\nÔnibus de subida e descida\nIngresso",
                'no_incluye' => "Alimentação\nGorjetas",
                'traducciones' => [
                    'es' => [
                        'nombre' => "Machu Picchu",
                        'descripcion' => "La maravilla del mundo. Visita guiada a la ciudadela inca, con opciones de tren y de entrada a la montaña.\n\nConsulta fechas y disponibilidad: las entradas se agotan con anticipación.",
                        'duracion' => "1 o 2 días",
                        'itinerario' => "Traslado a Ollantaytambo\nTren a Aguas Calientes\nBus hasta la ciudadela\nVisita guiada de 2 horas",
                        'incluye' => "Guía profesional\nTren de ida y vuelta\nBus de subida y bajada\nEntrada",
                        'no_incluye' => "Alimentación\nPropinas",
                    ],
                    'en' => [
                        'nombre' => "Machu Picchu",
                        'descripcion' => "The wonder of the world. Guided visit to the Inca citadel, with train and mountain-ticket options.\n\nAsk about dates and availability: tickets sell out well in advance.",
                        'duracion' => "1 or 2 days",
                        'itinerario' => "Transfer to Ollantaytambo\nTrain to Aguas Calientes\nBus up to the citadel\n2-hour guided visit",
                        'incluye' => "Professional guide\nRound-trip train\nBus up and down\nEntrance ticket",
                        'no_incluye' => "Meals\nTips",
                    ],
                ],
            ],
            [
                'nombre' => 'Montanha 7 Cores',
                'slug' => 'montanha-7-cores',
                'descripcion' => "Uma caminhada até a Vinicunca, a famosa montanha colorida, a mais de 5.000 metros de altitude.\n\nUma paisagem de tirar o fôlego.",
                'duracion' => 'Dia inteiro (saída de madrugada)',
                'precio' => 40,
                'imagen' => 'img/tours/montanha-7-cores.jpg',
                'itinerario' => "Saída às 3h da manhã\nCafé da manhã\nCaminhada até o mirante\nAlmoço e retorno a Cusco",
                'incluye' => "Guia em português\nTransporte\nCafé da manhã e almoço",
                'no_incluye' => "Ingresso da comunidade\nCavalo opcional",
                'traducciones' => [
                    'es' => [
                        'nombre' => "Montaña de 7 Colores",
                        'descripcion' => "Una caminata hasta Vinicunca, la famosa montaña de colores, a más de 5.000 metros de altitud.\n\nUn paisaje que quita el aliento.",
                        'duracion' => "Día completo (salida de madrugada)",
                        'itinerario' => "Salida a las 3 a. m.\nDesayuno\nCaminata hasta el mirador\nAlmuerzo y regreso a Cusco",
                        'incluye' => "Guía profesional\nTransporte\nDesayuno y almuerzo",
                        'no_incluye' => "Entrada a la comunidad\nCaballo opcional",
                    ],
                    'en' => [
                        'nombre' => "Rainbow Mountain",
                        'descripcion' => "A hike to Vinicunca, the famous colorful mountain, at over 5,000 meters above sea level.\n\nA breathtaking landscape.",
                        'duracion' => "Full day (pre-dawn departure)",
                        'itinerario' => "Departure at 3 a.m.\nBreakfast\nHike to the viewpoint\nLunch and return to Cusco",
                        'incluye' => "Professional guide\nTransport\nBreakfast and lunch",
                        'no_incluye' => "Community entrance fee\nOptional horse",
                    ],
                ],
            ],
            [
                'nombre' => 'Laguna Humantay',
                'slug' => 'laguna-humantay',
                'descripcion' => "Uma lagoa de águas turquesa aos pés da montanha Salkantay. Caminhada de cerca de 1h30 de subida.\n\nNatureza em sua forma mais pura.",
                'duracion' => 'Dia inteiro (saída de madrugada)',
                'precio' => 40,
                'imagen' => 'img/tours/humantay.jpg',
                'itinerario' => "Saída às 4h da manhã\nCafé da manhã em Mollepata\nCaminhada até a lagoa\nAlmoço e retorno a Cusco",
                'incluye' => "Guia em português\nTransporte\nCafé da manhã e almoço",
                'no_incluye' => "Ingresso da comunidade",
                'traducciones' => [
                    'es' => [
                        'nombre' => "Laguna Humantay",
                        'descripcion' => "Una laguna de aguas turquesa a los pies de la montaña Salkantay. Caminata de aproximadamente 1 h 30 de subida.\n\nNaturaleza en su forma más pura.",
                        'duracion' => "Día completo (salida de madrugada)",
                        'itinerario' => "Salida a las 4 a. m.\nDesayuno en Mollepata\nCaminata hasta la laguna\nAlmuerzo y regreso a Cusco",
                        'incluye' => "Guía profesional\nTransporte\nDesayuno y almuerzo",
                        'no_incluye' => "Entrada a la comunidad",
                    ],
                    'en' => [
                        'nombre' => "Humantay Lake",
                        'descripcion' => "A turquoise lake at the foot of Salkantay mountain. About a 1.5-hour uphill hike.\n\nNature in its purest form.",
                        'duracion' => "Full day (pre-dawn departure)",
                        'itinerario' => "Departure at 4 a.m.\nBreakfast in Mollepata\nHike to the lake\nLunch and return to Cusco",
                        'incluye' => "Professional guide\nTransport\nBreakfast and lunch",
                        'no_incluye' => "Community entrance fee",
                    ],
                ],
            ],
        ];

        foreach ($tours as $tour) {
            Tour::updateOrCreate(['slug' => $tour['slug']], $tour + ['activo' => true]);
        }
    }
}
