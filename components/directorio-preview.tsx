"use client"

interface ParticipanteProps {
  numero: number
  nombres: string
  apellidos: string
  cedula: string
  entidad: string
  email: string
  celular: string
  fotografia?: string
}

interface EscuelaInfo {
  nombre_completo: string
  nombre_estacion?: string
  nombre: string
  codigo_formato: string
  version_formato: string
  fecha_vigencia?: string
  slogan: string
  pie_pagina?: string
}

interface DirectorioPreviewProps {
  escuelaInfo?: EscuelaInfo
  participantes?: ParticipanteProps[]
  cursoNombre?: string
  numeroRegistro?: string
  fechaInicio?: string
  fechaFin?: string
}

export default function DirectorioPreview({
  escuelaInfo,
  participantes = [],
  cursoNombre = "SISTEMA COMANDO DE INCIDENTES BÁSICO PARA BOMBEROS",
  numeroRegistro = "184-2025",
  fechaInicio = "2025-07-21",
  fechaFin = "2025-07-23",
}: DirectorioPreviewProps) {
  const defaultEscuelaInfo: EscuelaInfo = {
    nombre_completo: "CUERPO DE BOMBEROS VOLUNTARIOS LOS SANTOS",
    nombre_estacion: "ESCUELA INTERNACIONAL DE BOMBEROS DEL ORIENTE COLOMBIANO",
    nombre: "ESIBOC",
    codigo_formato: "ESIBOC-FO-03",
    version_formato: "1",
    fecha_vigencia: "2024-12-14",
    slogan: "FORMATO DIRECTORIO FINALIZACIÓN DE CURSO",
    pie_pagina: "CUERPO BOMBEROS VOLUNTARIOS LOS SANTOS\nESCUELA INTERNACIONAL DE BOMBEROS DEL ORIENTE COLOMBIANO",
  }

  const defaultParticipantes: ParticipanteProps[] = [
    {
      numero: 1,
      nombres: "ARLEZ JUNIOR",
      apellidos: "ARBOLEDA CARVAJAL",
      cedula: "91447273",
      entidad: "CUERPO DE BOMBEROS VOLUNTARIOS LOS SANTOS",
      email: "arlez@bomberos.com",
      celular: "3001234567",
      fotografia: "",
    },
    {
      numero: 2,
      nombres: "BRAYAN STICK",
      apellidos: "MORENO SARMIENTO",
      cedula: "1096189269",
      entidad: "CUERPO DE BOMBEROS VOLUNTARIOS LOS SANTOS",
      email: "brayan@bomberos.com",
      celular: "3007654321",
      fotografia: "",
    },
  ]

  const escuela = escuelaInfo || defaultEscuelaInfo
  const participantesData = participantes.length > 0 ? participantes : defaultParticipantes

  const formatDate = (dateString: string) => {
    try {
      const date = new Date(dateString)
      const day = date.getDate()
      const month = date.toLocaleDateString("es-ES", { month: "long" })
      const year = date.getFullYear()
      return `${day} DE ${month.toUpperCase()} DE ${year}`
    } catch (error) {
      return dateString || "FECHA NO DISPONIBLE"
    }
  }

  const formatDateShort = (dateString: string) => {
    try {
      const date = new Date(dateString)
      return date.toLocaleDateString("es-ES", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
      })
    } catch (error) {
      return dateString || "00/00/0000"
    }
  }

  // Encabezado que se repite en cada página
  const PageHeader = () => (
    <div className="border-2 border-black p-3 mb-6">
      <div className="grid grid-cols-3 gap-4 items-start text-xs">
        <div className="text-left">
          <div className="font-bold">Código: {escuela.codigo_formato || "N/A"}</div>
          <div className="font-bold mt-1">FORMATO DIRECTORIO FINALIZACIÓN DE CURSO</div>
        </div>
        <div className="text-center">
          <div className="font-bold text-sm leading-tight">{escuela.nombre_completo || "ESCUELA DE BOMBEROS"}</div>
          {escuela.nombre_estacion && (
            <div className="font-bold text-sm leading-tight mt-1">{escuela.nombre_estacion}</div>
          )}
          <div className="font-bold text-sm leading-tight mt-1">{escuela.nombre || "ESIBOC"}</div>
        </div>
        <div className="text-right">
          <div className="font-bold">Versión: {escuela.version_formato || "1"}</div>
          <div className="font-bold mt-1">Vigente Desde:</div>
          <div className="font-bold">
            {escuela.fecha_vigencia
              ? formatDateShort(escuela.fecha_vigencia)
              : formatDateShort(new Date().toISOString())}
          </div>
        </div>
      </div>
    </div>
  )

  return (
    <div className="bg-white text-black font-sans">
      {/* Portada */}
      <div className="min-h-screen flex flex-col page-break">
        <PageHeader />

        {/* Contenido centrado de la portada */}
        <div className="flex-1 flex flex-col justify-center items-center text-center py-20">
          <h1 className="text-8xl font-bold mb-32 tracking-wider">DIRECTORIO</h1>

          <div className="space-y-6 text-xl max-w-4xl">
            <div className="font-bold text-2xl leading-relaxed">
              {(cursoNombre || "CURSO DE BOMBEROS").toUpperCase()}
            </div>
            <div className="font-bold text-xl">REGISTRO {numeroRegistro || "N/A"}</div>
            <div className="font-bold text-xl">
              DEL {formatDate(fechaInicio || "")} AL {formatDate(fechaFin || "")}
            </div>
          </div>
        </div>

        {/* Pie de página */}
        <div className="text-center text-lg font-bold leading-relaxed">
          {escuela.pie_pagina ? (
            <div className="whitespace-pre-line">{escuela.pie_pagina}</div>
          ) : (
            <div>
              {escuela.nombre_completo || "ESCUELA DE BOMBEROS"}
              <br />
              {escuela.nombre || "ESIBOC"}
            </div>
          )}
        </div>
      </div>

      {/* Páginas de Participantes */}
      {Array.from({ length: Math.ceil(participantesData.length / 4) }, (_, pageIndex) => {
        const startIndex = pageIndex * 4
        const pageParticipants = participantesData.slice(startIndex, startIndex + 4)

        return (
          <div key={pageIndex} className="min-h-screen page-break">
            <PageHeader />

            {pageIndex === 0 && (
              <div className="text-center mb-8">
                <h2 className="text-2xl font-bold">PARTICIPANTES</h2>
              </div>
            )}

            <div className="space-y-8">
              {pageParticipants.map((participante, index) => {
                const numeroGlobal = startIndex + index + 1

                return (
                  <div key={index} className="border-2 border-black p-4">
                    <div className="grid grid-cols-12 gap-4">
                      {/* Número del participante */}
                      <div className="col-span-1 flex items-start justify-center">
                        <div className="text-2xl font-bold mt-2">{numeroGlobal}</div>
                      </div>

                      {/* Información del participante */}
                      <div className="col-span-11 space-y-3">
                        {/* Primera fila: Nombres y Cédula */}
                        <div className="grid grid-cols-2 gap-8">
                          <div>
                            <div className="font-bold text-sm mb-1">NOMBRES Y APELLIDOS:</div>
                            <div className="font-bold text-lg leading-tight">
                              {((participante.nombres || "") + " " + (participante.apellidos || ""))
                                .trim()
                                .toUpperCase() || "NOMBRE NO DISPONIBLE"}
                            </div>
                          </div>
                          <div>
                            <div className="font-bold text-sm mb-1">CEDULA</div>
                            <div className="text-lg font-bold">{participante.cedula || "N/A"}</div>
                          </div>
                        </div>

                        {/* Segunda fila: Entidad */}
                        <div>
                          <div className="font-bold text-sm mb-1">ENTIDAD:</div>
                          <div className="font-bold text-base leading-tight">
                            {(participante.entidad || "NO ESPECIFICADA").toUpperCase()}
                          </div>
                        </div>

                        {/* Tercera fila: Email y Celular */}
                        <div className="grid grid-cols-2 gap-8">
                          <div>
                            <div className="font-bold text-sm mb-1">E-MAIL:</div>
                            <div className="text-base">{participante.email || ""}</div>
                          </div>
                          <div>
                            <div className="font-bold text-sm mb-1">CELULAR:</div>
                            <div className="text-base">{participante.celular || ""}</div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                )
              })}
            </div>
          </div>
        )
      })}

      {/* Página de Coordinador e Instructores */}
      <div className="min-h-screen page-break">
        <PageHeader />

        <div className="mb-8">
          <h2 className="text-2xl font-bold">COORDINADOR E INSTRUCTORES</h2>
        </div>

        <div className="space-y-8">
          {/* Ejemplo de coordinador - esto debería venir de la base de datos */}
          <div className="border-2 border-black p-4">
            <div className="grid grid-cols-12 gap-4">
              <div className="col-span-1 flex items-start justify-center">
                <div className="text-2xl font-bold mt-2">1</div>
              </div>
              <div className="col-span-11 space-y-3">
                <div className="grid grid-cols-2 gap-8">
                  <div>
                    <div className="font-bold text-sm mb-1">NOMBRES Y APELLIDOS:</div>
                    <div className="font-bold text-lg leading-tight">COORDINADOR DEL CURSO</div>
                  </div>
                  <div>
                    <div className="font-bold text-sm mb-1">CEDULA</div>
                    <div className="text-lg font-bold">00000000</div>
                  </div>
                </div>
                <div>
                  <div className="font-bold text-sm mb-1">ENTIDAD:</div>
                  <div className="font-bold text-base leading-tight">
                    {(escuela.nombre_completo || "ESCUELA DE BOMBEROS").toUpperCase()}
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-8">
                  <div>
                    <div className="font-bold text-sm mb-1">E-MAIL:</div>
                    <div className="text-base">coordinador@esiboc.edu.co</div>
                  </div>
                  <div>
                    <div className="font-bold text-sm mb-1">CELULAR:</div>
                    <div className="text-base">3000000000</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Página de Logística */}
      <div className="min-h-screen page-break">
        <PageHeader />

        <div className="mb-8">
          <h2 className="text-2xl font-bold">LOGISTICA</h2>
        </div>

        <div className="space-y-8">
          {/* Ejemplo de logística - esto debería venir de la base de datos */}
          <div className="border-2 border-black p-4">
            <div className="grid grid-cols-12 gap-4">
              <div className="col-span-1 flex items-start justify-center">
                <div className="text-2xl font-bold mt-2">1</div>
              </div>
              <div className="col-span-11 space-y-3">
                <div className="grid grid-cols-2 gap-8">
                  <div>
                    <div className="font-bold text-sm mb-1">NOMBRES Y APELLIDOS:</div>
                    <div className="font-bold text-lg leading-tight">RESPONSABLE DE LOGÍSTICA</div>
                  </div>
                  <div>
                    <div className="font-bold text-sm mb-1">CEDULA</div>
                    <div className="text-lg font-bold">00000000</div>
                  </div>
                </div>
                <div>
                  <div className="font-bold text-sm mb-1">ENTIDAD:</div>
                  <div className="font-bold text-base leading-tight">
                    {(escuela.nombre_completo || "ESCUELA DE BOMBEROS").toUpperCase()}
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-8">
                  <div>
                    <div className="font-bold text-sm mb-1">E-MAIL:</div>
                    <div className="text-base">logistica@esiboc.edu.co</div>
                  </div>
                  <div>
                    <div className="font-bold text-sm mb-1">CELULAR:</div>
                    <div className="text-base">3000000000</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <style jsx>{`
        @media print {
          .page-break {
            page-break-before: always;
          }
          .page-break:first-child {
            page-break-before: auto;
          }
        }
      `}</style>
    </div>
  )
}
