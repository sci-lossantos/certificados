"use client"

import DirectorioPreview from "../components/directorio-preview"

export default function SyntheticV0PageForDeployment() {
  const escuelaInfo = {
    nombre_completo: "CUERPO DE BOMBEROS VOLUNTARIOS LOS SANTOS",
    nombre_estacion: "ESCUELA INTERNACIONAL DE BOMBEROS DEL ORIENTE COLOMBIANO",
    nombre: "ESIBOC",
    codigo_formato: "ESIBOC-FO-03",
    version_formato: "1",
    fecha_vigencia: "2024-12-14",
    slogan: "FORMATO DIRECTORIO FINALIZACIÓN DE CURSO",
    pie_pagina: "CUERPO BOMBEROS VOLUNTARIOS LOS SANTOS\nESCUELA INTERNACIONAL DE BOMBEROS DEL ORIENTE COLOMBIANO",
  }

  const participantes = [
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
    {
      numero: 3,
      nombres: "CARLOS DAMIAN",
      apellidos: "BUSTAMANTE CEBALLOS",
      cedula: "1096203911",
      entidad: "CUERPO DE BOMBEROS VOLUNTARIOS LOS SANTOS",
      email: "carlos@bomberos.com",
      celular: "3009876543",
      fotografia: "",
    },
  ]

  return (
    <DirectorioPreview
      escuelaInfo={escuelaInfo}
      participantes={participantes}
      cursoNombre="SISTEMA COMANDO DE INCIDENTES BÁSICO PARA BOMBEROS"
      numeroRegistro="184-2025"
      fechaInicio="2025-07-21"
      fechaFin="2025-07-23"
    />
  )
}
