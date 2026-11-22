<?php

namespace Database\Seeders;

use App\Models\Seccion;
use App\Models\Tour;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Carga los 11 paquetes del PDF "tours a poner en la web".
 * Se puede correr varias veces: actualiza por slug y no duplica.
 * No toca los tours que ya existen (usa slugs nuevos).
 *
 *   php artisan db:seed --class=PaquetesCuscoSeeder
 *
 * Los paquetes sin precio quedan en 0 => la web muestra solo "Reservar" y WhatsApp.
 * Para poner un precio después: Admin > Tours > editar > Precios.
 */
class PaquetesCuscoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach ($this->paquetes() as $p) {
                $precios = $p['precios'] ?? [];
                $pasos = $p['pasos'];
                $secciones = $p['secciones'];
                unset($p['precios'], $p['pasos'], $p['secciones']);

                $p['precio'] = collect($precios)->pluck('precio')->filter(fn ($v) => $v > 0)->min() ?? 0;
                $p += ['idioma_base' => 'es', 'destino' => 'Cusco', 'descuento' => 0, 'activo' => true, 'borrador' => false];
                $p['itinerario'] = collect($pasos)->pluck('titulo')->implode("\n");

                $tour = Tour::updateOrCreate(['slug' => $p['slug']], $p);

                $tour->itinerarioItems()->delete();
                foreach ($pasos as $i => $paso) {
                    $tour->itinerarioItems()->create([
                        'orden' => $i, 'titulo' => $paso[0], 'horario' => $paso[1] ?? null, 'descripcion' => $paso[2] ?? null,
                    ]);
                }

                $tour->precios()->delete();
                foreach ($precios as $i => $pr) {
                    $tour->precios()->create([
                        'orden' => $i, 'etiqueta' => $pr['etiqueta'], 'precio' => $pr['precio'], 'descripcion' => $pr['descripcion'] ?? null,
                    ]);
                }

                $ids = Seccion::whereIn('slug', $secciones)->pluck('id')->all();
                $tour->secciones()->syncWithoutDetaching($ids);
            }
        });
    }

    private function paquetes(): array
    {
        return [
            // 1 ─────────────────────────────────────────────
            [
                'nombre' => 'Cusco Imperial: City Tour Privado por los Tesoros Incas',
                'slug' => 'cusco-imperial-city-tour-privado',
                'categoria' => 'Cultural', 'nivel' => 'Baixo', 'tamano_grupo' => 15,
                'duracion' => 'Medio día (mañana 8:30–13:00 o tarde 13:30–18:30)',
                'descripcion' => "Descubre la historia, arquitectura y cultura de la antigua capital del Imperio Inca con nuestro City Tour Privado por Cusco. Acompañado por un guía turístico profesional, recorrerás algunos de los monumentos históricos, templos y centros arqueológicos más representativos de la ciudad y sus alrededores.\n\nDurante esta experiencia conocerás la impresionante arquitectura de la Catedral del Cusco, el legado espiritual de Qorikancha y las extraordinarias construcciones incas de Sacsayhuamán, Qenqo, Puka Pukara y Tambomachay.\n\nDisfruta de un recorrido privado con transporte exclusivo, atención personalizada y la posibilidad de elegir entre un turno de mañana o tarde, para conocer Cusco con comodidad y a tu propio ritmo.\n\n## Atractivos principales\n- **Catedral del Cusco:** descubre su arquitectura colonial y sus valiosas obras de la Escuela Cusqueña.\n- **Qorikancha:** conoce el antiguo Templo del Sol y sus extraordinarios muros de piedra inca.\n- **Sacsayhuamán:** contempla sus enormes bloques de piedra y las vistas panorámicas de Cusco.\n- **Circuito arqueológico:** visita Qenqo, el bosque de eucaliptos, Puka Pukara y Tambomachay.",
                'incluye' => "Recojo desde tu hotel dentro del centro histórico de Cusco\nTransporte turístico privado\nRetorno a tu hotel\nGuía turístico profesional y acreditado\nGuiado en español, inglés o portugués, previa coordinación y disponibilidad\nAtención personalizada durante el recorrido",
                'no_incluye' => "Boleto Turístico del Cusco\nEntrada a la Catedral del Cusco\nEntrada a Qorikancha\nAlimentación y bebidas\nGastos personales\nPropinas\nRecojos fuera del centro histórico, salvo coordinación adicional",
                'info_importante' => "Turno mañana: 8:30 a. m. – 1:00 p. m. | Turno tarde: 1:30 p. m. – 6:30 p. m.\n\n## Entradas referenciales\n- Boleto Turístico Circuito I – extranjeros: S/ 70\n- Boleto Turístico Circuito I – peruanos: S/ 40\n- Qorikancha: S/ 20\n- Catedral del Cusco: S/ 40\n\nLas tarifas de las entradas son referenciales y deben confirmarse antes de la visita.",
                'meta_descripcion' => 'City Tour privado en Cusco: Catedral, Qorikancha, Sacsayhuamán, Qenqo, Puka Pukara y Tambomachay con guía profesional y transporte exclusivo.',
                'palabras_clave' => 'city tour cusco, tour privado cusco, qorikancha, sacsayhuaman, catedral del cusco',
                'secciones' => ['tour-clasico'],
                'precios' => [['etiqueta' => 'Por persona (desde 4 pasajeros)', 'precio' => 35]],
                'pasos' => [
                    ['Recojo del hotel', null, 'Recogida en tu alojamiento dentro del centro histórico de Cusco.'],
                    ['Catedral del Cusco', null, 'Visita al monumento religioso más emblemático de la Plaza de Armas.'],
                    ['Qorikancha', null, 'Recorrido por el antiguo Templo del Sol, uno de los centros religiosos más importantes del mundo inca.'],
                    ['Sacsayhuamán', null, 'Exploración de sus impresionantes estructuras de piedra y espacios ceremoniales.'],
                    ['Qenqo', null, 'Visita a sus formaciones rocosas talladas y antiguos espacios rituales.'],
                    ['Bosque de Eucaliptos de Qenqo', null, 'Parada para disfrutar del paisaje y tomar fotografías.'],
                    ['Puka Pukara', null, 'Visita a este conjunto arqueológico conocido como la Fortaleza Roja.'],
                    ['Tambomachay', null, 'Exploración de sus fuentes, canales y construcciones relacionadas con el culto al agua.'],
                    ['Retorno al hotel', null, 'Traslado de regreso al alojamiento dentro del centro histórico.'],
                ],
            ],
            // 2 ─────────────────────────────────────────────
            [
                'nombre' => 'Valle Sagrado VIP: Ruta de los Incas, Maras y Moray',
                'slug' => 'valle-sagrado-vip-maras-moray',
                'categoria' => 'Cultural', 'nivel' => 'Baixo', 'tamano_grupo' => 15,
                'duracion' => 'Día completo (salida 6:30 a. m., retorno 6:00–7:00 p. m.)',
                'descripcion' => "Explora los tesoros arqueológicos, culturales y naturales del Valle Sagrado de los Incas en una experiencia privada que reúne algunos de los destinos más fascinantes de Cusco. Acompañado por un guía turístico profesional, descubrirás paisajes andinos, antiguos centros arqueológicos, pueblos tradicionales y expresiones culturales que conservan la herencia de la civilización inca.\n\nDurante este recorrido visitarás Pisac, Urubamba, Chinchero, Moray, las Salineras de Maras y Ollantaytambo, disfrutando además de un almuerzo buffet y de la comodidad de un transporte privado.\n\nUna experiencia ideal para quienes desean conocer varios de los principales atractivos del Valle Sagrado en un solo día.\n\n## Atractivos principales\n- **Pisac:** explora sus impresionantes terrazas agrícolas, construcciones ceremoniales y mercado artesanal.\n- **Moray:** descubre las extraordinarias terrazas circulares utilizadas como espacios de experimentación agrícola.\n- **Salineras de Maras:** contempla miles de pozas de sal trabajadas tradicionalmente por las familias locales.\n- **Ollantaytambo:** recorre uno de los conjuntos arqueológicos y pueblos incas vivientes más importantes del Perú.",
                'incluye' => "Recojo desde tu hotel dentro del centro histórico de Cusco\nTransporte turístico privado\nRetorno a tu hotel\nGuía turístico profesional y acreditado\nGuiado en español, inglés o portugués, previa coordinación y disponibilidad\nAlmuerzo buffet en Urubamba\nAtención personalizada",
                'no_incluye' => "Boleto Turístico del Cusco\nEntrada a las Salineras de Maras\nBebidas durante el almuerzo\nDesayuno y cena\nGastos personales\nPropinas\nRecojos fuera del centro histórico, salvo coordinación adicional",
                'info_importante' => "## Entradas referenciales\n- Boleto Turístico Circuito III – extranjeros: S/ 70\n- Boleto Turístico Circuito III – peruanos: S/ 40\n- Salineras de Maras: pago independiente",
                'meta_descripcion' => 'Valle Sagrado VIP privado desde Cusco: Pisac, Chinchero, Moray, Salineras de Maras y Ollantaytambo con almuerzo buffet y guía profesional.',
                'palabras_clave' => 'valle sagrado, tour privado, pisac, moray, maras, ollantaytambo, chinchero',
                'secciones' => ['tour-clasico'],
                'precios' => [['etiqueta' => 'Por persona (desde 4 pasajeros)', 'precio' => 57]],
                'pasos' => [
                    ['Recojo en Cusco', '6:30 a. m.', 'Salida desde tu hotel o alojamiento.'],
                    ['Mirador de Taray', null, 'Panorámica del Valle Sagrado, sus montañas, campos agrícolas y el río Vilcanota.'],
                    ['Centro Arqueológico de Pisac', null, 'Terrazas, recintos ceremoniales, fuentes y construcciones de piedra.'],
                    ['Mercado artesanal de Pisac', null, 'Textiles, cerámicas, joyería y artesanías tradicionales.'],
                    ['Urubamba – Almuerzo buffet', null, 'Variedad de platos de la gastronomía peruana y regional.'],
                    ['Chinchero', null, 'Arquitectura inca y colonial y técnicas ancestrales de tejido.'],
                    ['Moray', null, 'Terrazas circulares y su importancia para la agricultura inca.'],
                    ['Salineras de Maras', null, 'Sistema tradicional de producción de sal por evaporación natural.'],
                    ['Ollantaytambo', null, 'Terrazas, templos, fuentes y calles que conservan parte del antiguo diseño urbano inca.'],
                    ['Retorno a Cusco', '6:00 – 7:00 p. m.', 'Traslado hacia tu hotel o punto previamente coordinado.'],
                ],
            ],
            // 3 ─────────────────────────────────────────────
            [
                'nombre' => 'Machu Picchu y Valle Sagrado: Aventura Inca de 2 Días',
                'slug' => 'machu-picchu-valle-sagrado-2-dias',
                'categoria' => 'Cultural', 'nivel' => 'Médio', 'tamano_grupo' => 12, 'badge' => 'Más vendido',
                'duracion' => '2 días / 1 noche',
                'descripcion' => "Vive una experiencia inolvidable explorando el Valle Sagrado de los Incas y la majestuosa ciudadela de Machu Picchu en un paquete de dos días que combina historia, arqueología, paisajes andinos y cultura tradicional.\n\nDurante el primer día recorrerás algunos de los principales atractivos del Valle Sagrado, incluyendo Pisac, Urubamba y Ollantaytambo. Disfrutarás de un almuerzo buffet y conocerás los paisajes y las tradiciones que conservan la herencia de la civilización inca. Al finalizar el recorrido, abordarás el tren turístico hacia Aguas Calientes, donde pasarás la noche.\n\nEl segundo día estará dedicado a descubrir la extraordinaria ciudadela inca, acompañado por un guía profesional que te ayudará a comprender su historia, arquitectura y significado cultural.\n\n## Atractivos principales\n- **Pisac:** uno de los centros arqueológicos más importantes del Valle Sagrado.\n- **Ollantaytambo:** arquitectura monumental y tradicionales calles incas.\n- **Viaje en tren:** paisajes del río Vilcanota y las montañas rumbo a Aguas Calientes.\n- **Machu Picchu:** la extraordinaria ciudadela inca con guía profesional.",
                'incluye' => "Día 1: Recojo desde tu hotel en Cusco\nDía 1: Transporte turístico durante el recorrido\nDía 1: Guía turístico profesional (español o inglés, según disponibilidad)\nDía 1: Visita guiada a Pisac\nDía 1: Visita a Ollantaytambo\nDía 1: Almuerzo buffet en Urubamba (sin bebidas)\nDía 1: Ticket de tren turístico de Ollantaytambo a Aguas Calientes\nDía 2: Bus turístico de ida y vuelta entre Aguas Calientes y Machu Picchu\nDía 2: Entrada a Machu Picchu, según circuito y horario confirmados\nDía 2: Guía profesional durante la visita\nDía 2: Ticket de tren de retorno de Aguas Calientes a Ollantaytambo\nDía 2: Transporte turístico de Ollantaytambo a Cusco y traslado a tu hotel\nAsistencia y coordinación",
                'no_incluye' => "Alojamiento en Aguas Calientes\nDesayuno del primer día\nAlmuerzo del segundo día\nCena del primer día\nBebidas durante los almuerzos\nBoleto Turístico del Cusco, Circuito III\nGastos personales\nPropinas\nIngresos adicionales a Huayna Picchu o Montaña Machu Picchu",
                'info_importante' => "El alojamiento en Aguas Calientes no está incluido y puede añadirse como servicio adicional, según disponibilidad.\n\n## Entradas\n- Boleto Turístico Circuito III – extranjeros: S/ 70\n- Boleto Turístico Circuito III – peruanos: S/ 40\n- Machu Picchu: según circuito y disponibilidad oficial",
                'meta_descripcion' => 'Machu Picchu en tren y Valle Sagrado en 2 días / 1 noche desde Cusco: Pisac, Ollantaytambo, tren a Aguas Calientes y visita guiada a Machu Picchu.',
                'palabras_clave' => 'machu picchu, valle sagrado, 2 dias, tren, aguas calientes, ollantaytambo',
                'secciones' => ['machu-picchu'],
                'adicionales' => [
                    ['nombre' => 'Alojamiento en Aguas Calientes', 'precio' => null],
                    ['nombre' => 'Upgrade de tren (según disponibilidad)', 'precio' => null],
                    ['nombre' => 'Transporte privado', 'precio' => null],
                    ['nombre' => 'Guía privado en Machu Picchu', 'precio' => null],
                    ['nombre' => 'Almuerzo en Aguas Calientes', 'precio' => null],
                    ['nombre' => 'Huayna Picchu', 'precio' => null],
                    ['nombre' => 'Montaña Machu Picchu', 'precio' => null],
                ],
                'precios' => [
                    ['etiqueta' => 'Por persona (1 pasajero)', 'precio' => 450],
                    ['etiqueta' => 'Por persona (desde 2 pasajeros)', 'precio' => 370],
                ],
                'pasos' => [
                    ['Cusco – Valle Sagrado: recojo y Taray', 'Día 1 · 7:00 a. m.', "Salida desde tu alojamiento en Cusco y parada en el Mirador de Taray con vistas panorámicas del Valle Sagrado."],
                    ['Pisac', 'Día 1', 'Complejo arqueológico, historia, arquitectura y terrazas agrícolas, más artesanía tradicional, textiles y joyería local.'],
                    ['Urubamba – Almuerzo buffet', 'Día 1', 'Gastronomía peruana y regional.'],
                    ['Ollantaytambo', 'Día 1', 'Complejo arqueológico, terrazas y tradicionales calles incas.'],
                    ['Tren a Aguas Calientes y noche', 'Día 1', 'Recorrido ferroviario por el valle, llegada al pueblo y descanso. El alojamiento no está incluido y puede añadirse según disponibilidad.'],
                    ['Machu Picchu: bus y visita guiada', 'Día 2', 'Desayuno, bus hasta la entrada del santuario y recorrido por la ciudadela según el circuito y horario de tu boleto.'],
                    ['Retorno a Cusco', 'Día 2', 'Descenso en bus, tiempo libre para almorzar, tren a Ollantaytambo y traslado a tu hotel en Cusco.'],
                ],
            ],
            // 4 ─────────────────────────────────────────────
            [
                'nombre' => 'Palccoyo: Montañas de Colores y Bosque de Piedras',
                'slug' => 'palccoyo-montanas-de-colores-bosque-de-piedras',
                'categoria' => 'Trekking', 'nivel' => 'Médio', 'tamano_grupo' => 15,
                'duracion' => 'Día completo (4:00 a. m. – 4:30 p. m. aprox.)',
                'descripcion' => "Descubre los extraordinarios paisajes de Palccoyo, una de las maravillas naturales de los Andes peruanos. Esta excursión te permitirá contemplar montañas de diferentes tonalidades, disfrutar de vistas privilegiadas del nevado Ausangate y conocer el tradicional pueblo de Checacupe.\n\nPalccoyo es una excelente alternativa para quienes desean explorar las montañas multicolores de Cusco mediante una caminata relativamente corta, rodeados de paisajes altoandinos, formaciones rocosas y naturaleza.\n\nDurante el recorrido también conocerás los históricos puentes Inca, Colonial y Republicano de Checacupe, disfrutarás de un desayuno y almuerzo en restaurantes locales y tendrás la oportunidad de explorar el fascinante Bosque de Piedras.\n\n## Atractivos principales\n- **Montañas de Colores Palccoyo:** contempla sus sorprendentes tonalidades naturales.\n- **Nevado Ausangate:** vistas hacia uno de los nevados más emblemáticos de Cusco.\n- **Checacupe:** sus tres puentes históricos, Inca, Colonial y Republicano.\n- **Bosque de Piedras:** formaciones naturales de la zona altoandina.",
                'incluye' => "Recojo desde el hotel en el centro histórico, sujeto a accesibilidad\nTransporte turístico compartido, según modalidad\nGuía profesional en español e inglés\nEntrada a Palccoyo\nDesayuno buffet básico\nAlmuerzo buffet\nBastones de trekking\nOxígeno disponible con el guía para situaciones de emergencia\nRetorno a los alrededores de Plaza Regocijo",
                'no_incluye' => "Gastos personales\nSouvenirs\nPropinas\nBebidas y alimentos adicionales\nServicios no especificados",
                'encuentro' => 'Recojo desde el hotel en el centro histórico, sujeto a accesibilidad. Retorno a los alrededores de Plaza Regocijo.',
                'info_importante' => "- Altitud aproximada: 4,900 a 5,000 m s. n. m., dependiendo del recorrido.\n- Caminata aproximada: 45 a 60 minutos de ida.\n- Modalidad compartida o privada, según contratación.\n- Se recomienda aclimatación previa.\n\n## Recomendaciones\n- Llevar ropa abrigadora y cortaviento.\n- Utilizar calzado de trekking.\n- Llevar guantes, gorro, protector solar y lentes.\n- Mantenerse hidratado y caminar a ritmo tranquilo.",
                'meta_descripcion' => 'Montaña de Colores Palccoyo y Bosque de Piedras desde Cusco: caminata corta, vistas al Ausangate y puentes de Checacupe. Compartido o privado.',
                'palabras_clave' => 'palccoyo, montaña de colores, montaña arcoiris, bosque de piedras, ausangate, checacupe',
                'secciones' => ['tour-mistico'],
                'precios' => [
                    ['etiqueta' => 'Servicio compartido (por persona)', 'precio' => 70],
                    ['etiqueta' => 'Servicio privado (por persona, desde 2 pasajeros)', 'precio' => 150],
                ],
                'pasos' => [
                    ['Recojo y desayuno', '4:00 – 6:30 a. m.', 'Salida de Cusco hacia el sur y parada en Cusipata para un desayuno buffet básico.'],
                    ['Checacupe', '7:00 – 9:00 a. m.', 'Visita a los puentes Inca, Colonial y Republicano, seguida de un recorrido por paisajes rurales.'],
                    ['Palccoyo', '9:00 – 11:30 a. m.', 'Caminata hacia los miradores de las montañas de colores. Tiempo para fotografías y visita opcional al Bosque de Piedras.'],
                    ['Descenso, almuerzo y retorno', '11:30 a. m. – 4:30 p. m.', 'Descenso al estacionamiento, almuerzo buffet en un restaurante rural y retorno a Cusco.'],
                ],
            ],
            // 5 ─────────────────────────────────────────────
            [
                'nombre' => 'Vinicunca: Aventura Privada en la Montaña Arcoíris',
                'slug' => 'vinicunca-aventura-privada-montana-arcoiris',
                'categoria' => 'Trekking', 'nivel' => 'Alto', 'tamano_grupo' => 15, 'badge' => 'Más vendido',
                'duracion' => 'Día completo (aprox. 12 horas)',
                'descripcion' => "Embárcate en una aventura privada hacia Vinicunca, la famosa Montaña de 7 Colores, uno de los paisajes naturales más extraordinarios de los Andes peruanos.\n\nDurante esta experiencia viajarás por los paisajes altoandinos de Cusco, atravesando comunidades tradicionales, valles, montañas y extensos campos donde podrás observar llamas y alpacas.\n\nAcompañado por un guía profesional, realizarás una caminata hacia los miradores de la Montaña Arcoíris, donde podrás contemplar sus impresionantes franjas de colores formadas por la composición mineral del terreno y disfrutar de las vistas del majestuoso nevado Ausangate.\n\nTambién tendrás la posibilidad de ampliar tu aventura visitando el Valle Rojo, un paisaje de tonalidades rojizas que complementa esta experiencia de montaña.\n\n## Atractivos principales\n- **Vinicunca:** la famosa Montaña de 7 Colores.\n- **Ausangate:** vistas del majestuoso nevado.\n- **Paisajes altoandinos:** comunidades rurales y zonas de pastoreo.\n- **Valle Rojo:** paisajes rojizos, visita opcional.",
                'incluye' => "Recojo desde el hotel en el centro histórico\nTransporte turístico privado\nGuía profesional\nGuiado en español e inglés; portugués previa confirmación\nDesayuno buffet\nAlmuerzo buffet\nEntrada a Vinicunca\nBastones de trekking, previa solicitud\nOxígeno disponible con el guía para situaciones de emergencia\nRetorno al hotel",
                'no_incluye' => "Entrada al Valle Rojo\nAlquiler de caballo o mula\nBebidas adicionales\nAlimentos no especificados\nPropinas\nGastos personales\nRecojos fuera del centro histórico, salvo coordinación",
                'info_importante' => "- Altitud máxima aproximada: 5,036 m s. n. m.\n- Duración aproximada: 12 horas.\n- Caminata de ascenso: aprox. 1 hora y 30 minutos. Descenso: aprox. 1 hora.\n- El Valle Rojo es opcional y tiene un ingreso adicional.\n- La experiencia se realiza en una zona de elevada altitud.\n\n## Recomendaciones\n- Aclimatarse en Cusco al menos dos días antes.\n- Llevar ropa abrigadora, cortaviento y guantes.\n- Utilizar calzado de trekking.\n- Llevar protector solar, lentes y agua.\n- Informar al guía si se presenta malestar por la altitud.",
                'meta_descripcion' => 'Tour privado a Vinicunca, la Montaña de 7 Colores, desde Cusco: desayuno y almuerzo buffet, guía profesional y oxígeno de emergencia.',
                'palabras_clave' => 'vinicunca, montaña de 7 colores, montaña arcoiris, valle rojo, ausangate, tour privado',
                'secciones' => ['tour-mistico'],
                'pasos' => [
                    ['Recojo en Cusco', '3:50 – 4:30 a. m.', 'Salida desde tu hotel hacia el sur de la región.'],
                    ['Desayuno en restaurante local', null, 'Parada para disfrutar de un desayuno buffet tradicional.'],
                    ['Traslado al punto de inicio', null, 'Recorrido por comunidades y paisajes altoandinos.'],
                    ['Ascenso a Vinicunca', null, 'Caminata de aproximadamente 1 hora y 30 minutos hacia los miradores de la Montaña de 7 Colores.'],
                    ['Tiempo en el mirador', null, 'Contemplación de las montañas, fotografías y descanso.'],
                    ['Valle Rojo (opcional)', null, 'Caminata adicional de aproximadamente 45 minutos, según condiciones del camino.'],
                    ['Descenso y almuerzo buffet', null, 'Retorno al estacionamiento y parada en un restaurante local.'],
                    ['Retorno a Cusco', null, 'Traslado al alojamiento en el centro histórico.'],
                ],
            ],
            // 6 ─────────────────────────────────────────────
            [
                'nombre' => 'Laguna Humantay: Caminata al Paraíso Turquesa de los Andes',
                'slug' => 'laguna-humantay-full-day',
                'categoria' => 'Trekking', 'nivel' => 'Médio', 'tamano_grupo' => 15,
                'duracion' => 'Día completo (aprox. 12 horas)',
                'descripcion' => "Descubre la impresionante Laguna Humantay, uno de los destinos naturales más hermosos de Cusco, rodeado de montañas, glaciares y paisajes de extraordinaria belleza.\n\nDurante esta excursión viajarás hacia Mollepata y Soraypampa, atravesando hermosos paisajes andinos hasta llegar al punto de inicio de la caminata.\n\nAcompañado por un guía profesional, caminarás hacia la laguna, donde podrás contemplar sus aguas turquesas y esmeraldas, rodeadas de los majestuosos nevados Salkantay y Humantay.\n\nUna experiencia ideal para quienes desean conectar con la naturaleza, disfrutar de paisajes de montaña y descubrir uno de los escenarios más impresionantes de los Andes peruanos.\n\n## Atractivos principales\n- **Laguna Humantay:** sus extraordinarias aguas turquesas.\n- **Nevado Salkantay:** una de las montañas más emblemáticas de Cusco.\n- **Soraypampa:** paisajes de montaña y punto de inicio de la caminata.\n- **Paisajes andinos:** naturaleza y tranquilidad de los Andes.",
                'incluye' => "Recojo desde tu alojamiento en el centro histórico\nTransporte turístico de ida y retorno\nGuía profesional de turismo\nGuiado en español e inglés; portugués sujeto a disponibilidad\nDesayuno\nAlmuerzo buffet\nEntrada a Laguna Humantay\nBastones de trekking, a solicitud\nAsistencia y orientación durante el recorrido",
                'no_incluye' => "Recojos fuera del centro histórico, salvo coordinación\nAlquiler de caballo\nBebidas adicionales\nSnacks\nGastos personales\nPropinas\nAlimentación no especificada",
                'info_importante' => "- Altitud máxima aproximada: 4,270 m s. n. m.\n- Duración: aproximadamente 12 horas.\n- Caminata: aprox. 1 h 30 min de subida y 1 h 30 min de bajada.\n- Dificultad: moderada a exigente.\n\n## Recomendaciones\n- Aclimatarse al menos dos días antes.\n- Llevar zapatillas de trekking y casaca abrigadora e impermeable.\n- Utilizar guantes, gorro, protector solar y lentes.\n- Llevar agua y snacks.",
                'meta_descripcion' => 'Excursión de día completo a la Laguna Humantay desde Cusco: Mollepata, Soraypampa, caminata guiada, desayuno y almuerzo buffet.',
                'palabras_clave' => 'laguna humantay, humantay, soraypampa, salkantay, mollepata, trekking cusco',
                'secciones' => ['tour-mistico'],
                'pasos' => [
                    ['Recojo en Cusco', '4:00 – 4:30 a. m.', 'Salida hacia Mollepata.'],
                    ['Desayuno en Mollepata', '6:30 – 7:30 a. m.', 'Desayuno en un restaurante local.'],
                    ['Viaje a Soraypampa', '7:30 – 8:30 a. m.', 'Traslado al punto de inicio de la caminata.'],
                    ['Caminata hacia Humantay', '8:30 – 10:30 a. m.', 'Ascenso aproximado de 1 hora y 30 minutos.'],
                    ['Tiempo en la laguna', '10:30 – 11:30 a. m.', 'Fotografías, descanso y contemplación del paisaje.'],
                    ['Descenso a Soraypampa', '11:30 a. m. – 1:00 p. m.', 'Retorno hacia Soraypampa.'],
                    ['Almuerzo en Mollepata', '1:00 – 2:30 p. m.', 'Almuerzo buffet.'],
                    ['Retorno a Cusco', '2:30 – 4:30 p. m.', 'Traslado de regreso.'],
                ],
            ],
            // 7 ─────────────────────────────────────────────
            [
                'nombre' => 'Ausangate 7 Lagunas: Trekking entre Lagunas y Montañas Sagradas',
                'slug' => 'ausangate-7-lagunas-full-day',
                'categoria' => 'Trekking', 'nivel' => 'Alto', 'tamano_grupo' => 15,
                'duracion' => 'Día completo (aprox. 14 horas)',
                'descripcion' => "Descubre uno de los paisajes naturales más impresionantes de la Cordillera del Vilcanota con nuestro Tour 7 Lagunas de Ausangate.\n\nEsta experiencia te permitirá explorar siete hermosas lagunas de diferentes tonalidades, rodeadas de majestuosos nevados, paisajes altoandinos y extensos pastizales donde podrás observar llamas y alpacas.\n\nDurante la caminata conocerás la belleza natural de las faldas del nevado Ausangate, una de las montañas más emblemáticas de Cusco y considerada un Apu sagrado dentro de la cosmovisión andina.\n\nAl finalizar el recorrido tendrás la oportunidad de relajarte en las aguas termales naturales de Pacchanta, rodeado de un espectacular paisaje de montaña.\n\n## Atractivos principales\n- **Las siete lagunas:** tonalidades azules, turquesas, verdes y rojizas.\n- **Nevado Ausangate:** una de las montañas sagradas más importantes de los Andes.\n- **Paisajes altoandinos:** naturaleza, pastizales y fauna local.\n- **Aguas termales de Pacchanta:** relájate después de la caminata.",
                'incluye' => "Recojo desde tu alojamiento en el centro histórico\nTransporte turístico de ida y retorno\nGuía profesional\nGuiado en español e inglés; portugués sujeto a disponibilidad\nIngreso a las 7 Lagunas de Ausangate\nDesayuno\nAlmuerzo\nBastones de trekking, a solicitud\nAsistencia y orientación durante la excursión",
                'no_incluye' => "Entrada a los baños termales de Pacchanta (referencia S/ 10)\nAlquiler de caballos\nBebidas adicionales\nSnacks\nGastos personales\nPropinas\nRecojos fuera del centro histórico, salvo coordinación",
                'info_importante' => "- Duración: aproximadamente 14 horas.\n- Altitud máxima: 4,646 m s. n. m.\n- Distancia: aproximadamente 13 km ida y vuelta.\n- Tiempo de caminata: aproximadamente 6 horas.\n- Dificultad: moderada a exigente. Punto de inicio: Pacchanta.\n\n## Alquiler de caballos\nPrecios referenciales: solo subida S/ 100, ida y vuelta S/ 150. Se contrata directamente con los pobladores locales, sujeto a disponibilidad, y no está incluido.\n\n## Recomendaciones\n- Aclimatarse al menos dos días antes.\n- Llevar calzado de trekking, ropa abrigadora y cortavientos.\n- Llevar gorro, guantes, protector solar, agua y snacks.\n- Llevar efectivo para baños termales y gastos personales.",
                'meta_descripcion' => 'Tour 7 Lagunas de Ausangate de día completo desde Cusco: trekking entre lagunas y nevados con aguas termales en Pacchanta.',
                'palabras_clave' => 'ausangate, 7 lagunas, pacchanta, aguas termales, trekking cusco, cordillera vilcanota',
                'secciones' => ['tour-mistico'],
                'pasos' => [
                    ['Recojo del hotel', '4:00 a. m.', 'Salida hacia Pacchanta.'],
                    ['Desayuno en Pacchanta', '7:30 – 8:00 a. m.', 'Desayuno en la comunidad de Pacchanta.'],
                    ['Caminata por las 7 Lagunas', '8:00 a. m. – 1:30 p. m.', "Azulqocha, Huchuypukaqocha, Hatunpukaqocha, Oqeqocha, Otorongo Macho, Otorongo Hembra y Chuñuqocha."],
                    ['Retorno a Pacchanta', '1:30 – 3:30 p. m.', 'Oportunidad para disfrutar de las aguas termales y almorzar.'],
                    ['Retorno a Cusco', '3:30 – 7:00 p. m.', 'Traslado a tu alojamiento en el centro histórico.'],
                ],
            ],
            // 8 ─────────────────────────────────────────────
            [
                'nombre' => 'Q’eswachaka: Puente Inca y Ruta de las 4 Lagunas',
                'slug' => 'qeswachaka-puente-inca-4-lagunas',
                'categoria' => 'Cultural', 'nivel' => 'Médio', 'tamano_grupo' => 15,
                'duracion' => 'Día completo (aprox. 12 horas)',
                'descripcion' => "Embárcate en una fascinante aventura por los Andes del sur de Cusco y descubre uno de los legados más extraordinarios de la civilización inca: el puente colgante de Q’eswachaka. Construido artesanalmente con fibras vegetales, este impresionante puente conserva una antigua tradición de ingeniería andina que ha pasado de generación en generación.\n\nDurante el recorrido conocerás el cañón del Apurímac, el pequeño volcán de Pabellones y el hermoso circuito de cuatro lagunas: Pampamarca, Asnacocha, Acopia y Pomacanchi.\n\nDisfruta de paisajes naturales, pueblos tradicionales y una experiencia cultural que te permitirá conocer otra faceta de la riqueza histórica y natural del Perú.\n\n## Atractivos principales\n- **Puente Inca Q’eswachaka:** conoce y cruza, sujeto a condiciones de seguridad, el puente tradicional de fibras vegetales.\n- **Cañón del Apurímac:** el espectacular paisaje del río y sus profundidades.\n- **Mini Volcán de Pabellones:** una singular formación geológica.\n- **Circuito de las 4 Lagunas:** Pampamarca, Asnacocha, Acopia y Pomacanchi.",
                'incluye' => "Recojo desde tu alojamiento en el centro histórico\nTransporte turístico\nGuía profesional en español e inglés\nDesayuno\nAlmuerzo\nBotiquín de primeros auxilios a bordo\nAsistencia y orientación\nOpciones vegetarianas previa solicitud",
                'no_incluye' => "Entrada al puente Q’eswachaka (referencia S/ 10)\nEntrada al volcán de Pabellones (referencia S/ 2)\nBebidas adicionales\nSnacks\nGastos personales\nPropinas\nAlimentación no especificada",
                'info_importante' => "- Duración: aproximadamente 12 horas.\n- Altitud del puente: aproximadamente 3,700 m s. n. m.\n- Dificultad: moderada.\n- Modalidad: compartido o privado, previa coordinación.\n- Tipo de experiencia: cultural, histórica y paisajística.\n\n## Recomendaciones\n- Llevar documento de identidad o pasaporte.\n- Utilizar ropa abrigadora, cortavientos y zapatillas cómodas.\n- Usar protector solar y lentes de sol; llevar gorra, agua y snacks.\n- Llevar efectivo para entradas y gastos personales.",
                'meta_descripcion' => 'Tour a Q’eswachaka, el último puente inca de fibras vegetales, con cañón del Apurímac, volcán de Pabellones y circuito de las 4 lagunas desde Cusco.',
                'palabras_clave' => 'qeswachaka, puente inca, 4 lagunas, pomacanchi, pabellones, apurimac, cusco',
                'secciones' => ['experiencias-diferentes'],
                'pasos' => [
                    ['Recojo en Cusco', '4:30 a. m.', 'Salida hacia el Valle Sur.'],
                    ['Desayuno en Cusipata', '6:30 – 7:30 a. m.', 'Desayuno en un restaurante local.'],
                    ['Mirador de Combapata', '7:30 – 8:30 a. m.', 'Paisajes andinos.'],
                    ['Mini Volcán de Pabellones', '8:30 – 9:00 a. m.', 'Visita a esta formación geológica.'],
                    ['Viaje hacia Q’ewe', '9:00 – 10:00 a. m.', 'Recorrido por Yanaoca y localidades tradicionales.'],
                    ['Puente Q’eswachaka', '10:00 – 11:30 a. m.', 'Visita, recorrido y cruce del puente, según condiciones de seguridad.'],
                    ['Circuito de las cuatro lagunas', '11:30 a. m. – 1:30 p. m.', 'Pampamarca, Asnacocha, Acopia y Pomacanchi.'],
                    ['Almuerzo en Cusipata', '2:00 – 3:00 p. m.', 'Parada para almorzar.'],
                    ['Retorno a Cusco', '3:00 – 5:00 p. m.', 'Traslado de regreso.'],
                ],
            ],
            // 9 ─────────────────────────────────────────────
            [
                'nombre' => 'Cusco Misterioso: 5 Días entre Incas, Andes y Machu Picchu',
                'slug' => 'cusco-misterioso-5-dias-4-noches',
                'categoria' => 'Cultural', 'nivel' => 'Médio', 'tamano_grupo' => 12,
                'duracion' => '5 días / 4 noches',
                'descripcion' => "Descubre los principales atractivos turísticos de Cusco con nuestro paquete Cusco Misterioso de 5 días y 4 noches, una experiencia que combina historia, arqueología, tradiciones andinas, naturaleza y cultura.\n\nExplora la antigua capital del Tahuantinsuyo, recorre los monumentos históricos del centro de Cusco y descubre los extraordinarios complejos arqueológicos que conservan el legado de la civilización inca.\n\nDurante tu viaje conocerás el Valle Sagrado, sus pueblos tradicionales y centros arqueológicos, además de las sorprendentes Salineras de Maras y las terrazas circulares de Moray.\n\nEl momento más esperado será la visita a Machu Picchu, una de las maravillas del mundo moderno.\n\n## Atractivos principales\n- **Cusco histórico:** la Catedral, Qorikancha y los principales centros arqueológicos.\n- **Valle Sagrado:** Pisac, Urubamba, Ollantaytambo y Chinchero.\n- **Maras y Moray:** pozas de sal y terrazas agrícolas circulares.\n- **Machu Picchu:** la majestuosa ciudadela inca.",
                'incluye' => "Recepción en el aeropuerto\nTraslado aeropuerto – hotel y hotel – aeropuerto\nTransporte para City Tour, Valle Sagrado y Maras y Moray\nTransporte Cusco – Ollantaytambo – Cusco para Machu Picchu\nCity Tour en servicio compartido\nValle Sagrado en servicio compartido\nMaras y Moray en servicio compartido\nGuiado privado en Machu Picchu\nGuía profesional en español e inglés\nAsistencia y coordinación\nEntrada a Machu Picchu, según circuito confirmado\nBoleto Turístico del Cusco para los atractivos incluidos\nEntrada a las Salineras de Maras",
                'no_incluye' => "Alojamiento en Cusco, salvo contratación adicional\nAlimentación, salvo servicios expresamente contratados\nDesayunos, almuerzos y cenas no especificados\nDesayuno temprano antes de las 5:00 a. m.\nGastos personales\nBebidas\nPropinas\nServicios no mencionados\nBoletos de tren y buses a Machu Picchu, salvo que estén expresamente incluidos en la cotización final",
                'info_importante' => "- Duración: 5 días / 4 noches. Destino: Cusco y Machu Picchu.\n- Modalidad: excursiones compartidas y guiado privado en Machu Picchu.\n- Punto de inicio y finalización: Cusco.\n- Circuito de Machu Picchu sujeto a disponibilidad.\n- Se recomienda reservar con anticipación y llegar a Cusco con tiempo para aclimatarse.\n- Los trenes y buses de Machu Picchu pueden no estar incluidos salvo que se contraten expresamente. Consulta la cotización final.",
                'meta_descripcion' => 'Paquete Cusco Misterioso de 5 días y 4 noches: city tour, Valle Sagrado, Maras y Moray y Machu Picchu con guía privado.',
                'palabras_clave' => 'paquete cusco 5 dias, machu picchu, valle sagrado, maras, moray, cusco misterioso',
                'secciones' => ['machu-picchu'],
                'pasos' => [
                    ['Bienvenida a Cusco – City Tour', 'Día 1', 'Recepción en el aeropuerto, traslado al hotel y tiempo para aclimatarse. Visita a la Catedral, Qorikancha, Sacsayhuamán, Qenqo, Puka Pukara y Tambomachay. Noche en Cusco.'],
                    ['Valle Sagrado: Pisac, Urubamba, Ollantaytambo y Chinchero', 'Día 2', 'Mirador de Taray, Pisac y su mercado tradicional, almuerzo en Urubamba, Ollantaytambo y Chinchero con centro de interpretación textil. Noche en Cusco.'],
                    ['Cusco – Machu Picchu – Retorno a Cusco', 'Día 3', 'Recojo temprano, traslado a Ollantaytambo, tren a Aguas Calientes, bus a Machu Picchu y visita guiada según el circuito asignado. Tiempo libre para almorzar, tren de retorno y transporte a Cusco (llegada aprox. 8:00 p. m.).'],
                    ['Maras y Moray', 'Día 4', 'Salineras de Maras y su proceso tradicional de extracción de sal, y terrazas circulares del complejo de Moray. Noche en Cusco.'],
                    ['Despedida de Cusco', 'Día 5', 'Tiempo libre después del desayuno, posibilidad de recorrer mercados y calles históricas, y traslado al aeropuerto. Fin de nuestros servicios.'],
                ],
            ],
            // 10 ────────────────────────────────────────────
            [
                'nombre' => 'Picnic Andino con Llamas: Maras, Moray y Chinchero',
                'slug' => 'picnic-andino-con-llamas-maras-moray-chinchero',
                'categoria' => 'Experiencia', 'nivel' => 'Baixo', 'tamano_grupo' => 12,
                'duracion' => '6 a 7 horas',
                'descripcion' => "Descubre la belleza del Valle Sagrado de los Incas a través de una experiencia que combina tradición, historia, naturaleza y gastronomía.\n\nDurante este recorrido visitarás Chinchero, Moray y las famosas Salineras de Maras, tres lugares que conservan parte del legado cultural y la sabiduría ancestral de los Andes. Conocerás las técnicas tradicionales de tejido andino, los sorprendentes andenes circulares de Moray y las tradicionales pozas de sal de Maras.\n\nEl momento especial del día llegará con un picnic privado en un entorno natural, acompañado de llamas y una selección de productos locales. Será una oportunidad para relajarte, disfrutar de los paisajes y compartir un momento diferente en medio de la naturaleza andina.\n\n## Atractivos principales\n- **Chinchero:** la tradición textil andina y sus técnicas ancestrales.\n- **Moray:** terrazas circulares y extraordinaria arquitectura agrícola.\n- **Salineras de Maras:** la tradicional producción de sal.\n- **Picnic privado con llamas:** experiencia gastronómica al aire libre.",
                'incluye' => "Recojo desde alojamientos del centro histórico\nTransporte turístico compartido durante las visitas a Chinchero, Moray y Maras\nGuía profesional en español o inglés; portugués sujeto a disponibilidad\nTransporte privado desde el lugar del picnic hasta el centro histórico\nPicnic privado: tabla de quesos andinos\nPicnic privado: pan artesanal de masa madre con semillas\nPicnic privado: selección de frutos secos y frutas de temporada\nPicnic privado: bebida e infusiones\nPicnic privado: una botella de vino Intipalka, tinto o blanco",
                'no_incluye' => "Boleto Turístico del Cusco\nEntrada a las Salineras de Maras\nBebidas adicionales\nAlimentación no especificada\nPropinas\nRecojos fuera del centro histórico\nGastos personales",
                'info_importante' => "## Opcional: Picnic Lunch (+ USD 8 por persona)\n- **Entradas:** causa de pollo, ensalada orgánica o crema de zapallo.\n- **Platos principales:** lomo saltado, trucha con tubérculos o pasta con hongos.\n- **Postres:** tiramisú o brownie con helado.\n\nLos platos se preparan al momento y esta alternativa puede requerir un tiempo adicional de espera.\n\n## Entradas referenciales\n- Boleto Turístico Circuito III – extranjeros: S/ 70\n- Boleto Turístico Circuito III – peruanos: S/ 40\n- Salineras de Maras: S/ 20\n\n## Recomendaciones\n- Desayunar antes de iniciar la actividad.\n- Llevar ropa cómoda, zapatillas para caminar, protector solar, lentes de sol, sombrero y agua.\n- Llevar efectivo e informar previamente sobre restricciones alimentarias.",
                'meta_descripcion' => 'Picnic privado con llamas en el Valle Sagrado, con visita a Chinchero, Moray y las Salineras de Maras. Quesos andinos, pan artesanal y vino Intipalka.',
                'palabras_clave' => 'picnic con llamas, maras, moray, chinchero, valle sagrado, experiencia gastronomica',
                'secciones' => ['experiencias-diferentes'],
                'adicionales' => [['nombre' => 'Picnic Lunch (platos preparados al momento)', 'precio' => 8]],
                'pasos' => [
                    ['Recojo en Cusco', '8:00 a. m.', 'Salida desde tu alojamiento dentro de la zona de cobertura.'],
                    ['Chinchero – Tradición textil', null, 'Visita a un centro de interpretación textil.'],
                    ['Moray – Laboratorio agrícola inca', null, 'Recorrido por sus terrazas circulares.'],
                    ['Salineras de Maras', null, 'Visita a las pozas de sal y establecimiento local.'],
                    ['Picnic andino con llamas', null, 'Experiencia privada en un espacio natural.'],
                    ['Retorno a Cusco', '3:30 p. m.', 'Traslado privado desde el lugar del picnic hacia el centro histórico.'],
                ],
            ],
            // 11 ────────────────────────────────────────────
            [
                'nombre' => 'Machu Picchu Full Day: Experiencia Privada en Tren',
                'slug' => 'machu-picchu-full-day-tren-privado',
                'categoria' => 'Cultural', 'nivel' => 'Médio', 'tamano_grupo' => 12, 'badge' => 'Más vendido',
                'duracion' => '1 día completo',
                'descripcion' => "Embárcate en una aventura hacia uno de los destinos más extraordinarios del mundo: Machu Picchu, la joya arquitectónica de la civilización inca y una de las Siete Maravillas del Mundo Moderno.\n\nDurante esta experiencia de día completo viajarás en tren desde Ollantaytambo hasta Aguas Calientes, atravesando paisajes espectaculares de montañas, ríos y vegetación andina que poco a poco da paso al entorno natural de ceja de selva.\n\nAl llegar al santuario histórico, disfrutarás de una visita guiada privada junto a un guía profesional, quien te ayudará a comprender la historia, arquitectura, organización y significado cultural de esta antigua ciudad inca.\n\n## Atractivos principales\n- **Viaje en tren:** paisajes del valle del río Vilcanota.\n- **Aguas Calientes:** el pueblo que sirve de acceso al santuario.\n- **Machu Picchu:** la ciudadela inca con un guía privado.\n- **Arquitectura inca:** terrazas, templos, plazas y antiguos caminos.",
                'incluye' => "Coordinación previa y reunión informativa un día antes\nBoleto de ingreso a Machu Picchu, sujeto a disponibilidad (circuito 2A/2B, 1B o 3B según disponibilidad)\nGuía profesional privado dentro del santuario\nGuiado en español, inglés o portugués, previa coordinación\nRecojo desde tu hotel en el centro histórico\nTransporte compartido de ida y vuelta entre Cusco y Ollantaytambo\nTicket de tren turístico de ida y vuelta entre Ollantaytambo y Aguas Calientes\nTicket de bus turístico de subida y bajada entre Aguas Calientes y Machu Picchu\nTraslado de retorno hasta tu alojamiento",
                'no_incluye' => "Alimentación durante el recorrido\nBebidas\nGastos personales\nPropinas\nRecojos fuera de la zona de cobertura\nGuiado durante traslados o en Aguas Calientes\nEntradas a Huayna Picchu o Montaña Machu Picchu\nServicios adicionales no especificados",
                'info_importante' => "- Machu Picchu tiene una alta demanda de visitantes; se recomienda reservar con anticipación.\n- La disponibilidad de circuitos y horarios depende del sistema oficial de entradas. Cada boleto permite recorrer únicamente el circuito indicado.\n- Huayna Picchu y Montaña Machu Picchu requieren reservas específicas.\n- Los horarios de tren y transporte se organizan según disponibilidad.\n- Llevar pasaporte o documento de identidad original utilizado para la compra de la entrada.\n- Llevar ropa cómoda, calzado adecuado, protección solar, repelente y agua.\n\n## Opcional\nTransporte privado Cusco – Ollantaytambo – Cusco. También se pueden coordinar alternativas como pasar una noche en Aguas Calientes u Ollantaytambo.",
                'meta_descripcion' => 'Machu Picchu en un día desde Cusco: tren Ollantaytambo – Aguas Calientes, bus y visita guiada privada a la ciudadela inca.',
                'palabras_clave' => 'machu picchu full day, machu picchu en tren, tour privado, aguas calientes, ollantaytambo',
                'secciones' => ['machu-picchu'],
                'adicionales' => [['nombre' => 'Transporte privado Cusco – Ollantaytambo – Cusco', 'precio' => null]],
                'pasos' => [
                    ['Recojo en Cusco', '4:00 a. m.', 'Traslado desde tu alojamiento en el centro histórico hacia Ollantaytambo.'],
                    ['Viaje en tren', null, 'Recorrido ferroviario hacia Aguas Calientes.'],
                    ['Llegada a Aguas Calientes', null, 'Indicaciones para abordar el bus turístico.'],
                    ['Bus a Machu Picchu', null, 'Traslado hasta la entrada del santuario.'],
                    ['Visita guiada privada', null, 'Recorrido de aproximadamente 2 a 3 horas por el circuito indicado en tu boleto.'],
                    ['Retorno a Aguas Calientes', null, 'Descenso en bus y tiempo libre, según horario ferroviario.'],
                    ['Regreso en tren', null, 'Viaje hacia Ollantaytambo.'],
                    ['Traslado a Cusco', null, 'Retorno hasta tu alojamiento.'],
                ],
            ],
        ];
    }
}
