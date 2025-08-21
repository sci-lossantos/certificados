"use client"

import { useState, useEffect } from "react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Textarea } from "@/components/ui/textarea"
import { Switch } from "@/components/ui/switch"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Badge } from "@/components/ui/badge"
import { AlertCircle, Save, Eye, Copy, Settings, FileText } from "lucide-react"
import { Alert, AlertDescription } from "@/components/ui/alert"

interface CertificateConfig {
  id?: number
  nombre: string
  descripcion: string
  escuela_id?: number

  // Textos del certificado
  texto_certifica_que: string
  texto_identificado_con: string
  texto_asistio_aprobo: string
  texto_curso_autorizado: string
  texto_bajo_acta: string
  texto_duracion: string
  texto_realizado_en: string
  texto_constancia: string

  // Configuración de numeración
  mostrar_consecutivo: boolean
  formato_consecutivo: string
  numero_registro_base: string

  // Configuración de actas
  mostrar_numero_acta: boolean
  formato_numero_acta: string

  // Configuración de firmas
  mostrar_firma_director_nacional: boolean
  mostrar_firma_director_escuela: boolean
  mostrar_firma_coordinador: boolean

  // Configuración de contenido programático
  mostrar_contenido_programatico: boolean
  columnas_contenido: number

  // Configuración de encabezados
  mostrar_logos_institucionales: boolean
  texto_encabezado_izquierdo: string
  texto_encabezado_centro: string
  texto_encabezado_derecho: string

  // Configuración de autoridades
  director_nacional_nombre: string
  director_nacional_cargo: string
  comandante_nombre: string
  comandante_cargo: string

  // Configuración de diseño
  orientacion: "vertical" | "horizontal"
  tamaño_papel: string
  margenes_superior: number
  margenes_inferior: number
  margenes_izquierdo: number
  margenes_derecho: number

  activo: boolean
  es_plantilla_defecto: boolean
}

interface School {
  id: number
  nombre: string
  codigo: string
  nombre_completo: string
}

export default function CertificateConfigManager() {
  const [configs, setConfigs] = useState<CertificateConfig[]>([])
  const [schools, setSchools] = useState<School[]>([])
  const [selectedConfig, setSelectedConfig] = useState<CertificateConfig | null>(null)
  const [isEditing, setIsEditing] = useState(false)
  const [isLoading, setIsLoading] = useState(false)
  const [message, setMessage] = useState("")
  const [error, setError] = useState("")

  // Configuración por defecto basada en el formato ESIBOC
  const defaultConfig: CertificateConfig = {
    nombre: "",
    descripcion: "",
    texto_certifica_que: "Certifica que:",
    texto_identificado_con: "Identificado con C.C. No.",
    texto_asistio_aprobo: "Asistió y aprobó los requisitos del Curso:",
    texto_curso_autorizado:
      "Curso autorizado bajo registro Nro. {registro_curso} de la Dirección Nacional de Bomberos Colombia",
    texto_bajo_acta: "Bajo acta número {numero_acta} del {fecha_acta} del Cuerpo de Bomberos Voluntarios Los Santos",
    texto_duracion: "Con una duración de: {horas} HORAS",
    texto_realizado_en:
      "Realizado en ({lugar_realizacion}) del ({fecha_inicio_dia}) de ({fecha_inicio_mes}) al ({fecha_fin_dia}) de ({fecha_fin_mes}) de {año}",
    texto_constancia:
      "En constancia de lo anterior, se firma a los {fecha_firma_dia} dias del mes de {fecha_firma_mes} de {fecha_firma_año}",
    mostrar_consecutivo: true,
    formato_consecutivo: "{año}-{registro_curso}-{orden_alfabetico}",
    numero_registro_base: "184-2025",
    mostrar_numero_acta: true,
    formato_numero_acta: "{numero_acta}",
    mostrar_firma_director_nacional: true,
    mostrar_firma_director_escuela: true,
    mostrar_firma_coordinador: true,
    mostrar_contenido_programatico: true,
    columnas_contenido: 2,
    mostrar_logos_institucionales: true,
    texto_encabezado_izquierdo: "Cuerpo de Bomberos Los Santos Santander",
    texto_encabezado_centro: "Escuela Internacional de Bomberos del Oriente Colombiano\nESIBOC",
    texto_encabezado_derecho: "",
    director_nacional_nombre: "CT. EN JEFE LINA MARÍA MARÍN RODRÍGUEZ",
    director_nacional_cargo: "Directora Nacional DNBC",
    comandante_nombre: "CT. MANUEL ENRIQUE SALAZAR HERNANDEZ",
    comandante_cargo: "Comandante Cuerpo de Bomberos Los Santos Sant.",
    orientacion: "vertical",
    tamaño_papel: "A4",
    margenes_superior: 20,
    margenes_inferior: 20,
    margenes_izquierdo: 15,
    margenes_derecho: 15,
    activo: true,
    es_plantilla_defecto: false,
  }

  const [formData, setFormData] = useState<CertificateConfig>(defaultConfig)

  useEffect(() => {
    loadConfigs()
    loadSchools()
  }, [])

  const loadConfigs = async () => {
    // Simular carga de configuraciones
    const mockConfigs: CertificateConfig[] = [
      {
        id: 1,
        nombre: "Configuración ESIBOC Estándar",
        descripcion: "Configuración estándar para certificados ESIBOC",
        escuela_id: 2,
        ...defaultConfig,
        es_plantilla_defecto: true,
      },
    ]
    setConfigs(mockConfigs)
  }

  const loadSchools = async () => {
    // Simular carga de escuelas
    const mockSchools: School[] = [
      {
        id: 1,
        nombre: "Escuela Nacional de Bomberos",
        codigo: "ENB001",
        nombre_completo: "BOMBEROS VOLUNTARIOS LOS SANTOS",
      },
      {
        id: 2,
        nombre: "ESIBOC",
        codigo: "123",
        nombre_completo: "CUERPO DE BOMBEROS VOLUNTARIOS LOS SANTOS",
      },
    ]
    setSchools(mockSchools)
  }

  const handleSave = async () => {
    setIsLoading(true)
    try {
      // Aquí iría la lógica para guardar en la base de datos
      console.log("[v0] Guardando configuración:", formData)

      if (isEditing && selectedConfig?.id) {
        // Actualizar configuración existente
        setConfigs((prev) =>
          prev.map((config) => (config.id === selectedConfig.id ? { ...formData, id: selectedConfig.id } : config)),
        )
        setMessage("Configuración actualizada exitosamente")
      } else {
        // Crear nueva configuración
        const newConfig = { ...formData, id: Date.now() }
        setConfigs((prev) => [...prev, newConfig])
        setMessage("Configuración creada exitosamente")
      }

      setIsEditing(false)
      setSelectedConfig(null)
    } catch (err) {
      setError("Error al guardar la configuración")
    } finally {
      setIsLoading(false)
    }
  }

  const handleEdit = (config: CertificateConfig) => {
    setFormData(config)
    setSelectedConfig(config)
    setIsEditing(true)
  }

  const handleNew = () => {
    setFormData(defaultConfig)
    setSelectedConfig(null)
    setIsEditing(true)
  }

  const handleCancel = () => {
    setIsEditing(false)
    setSelectedConfig(null)
    setFormData(defaultConfig)
  }

  const handlePreview = (config: CertificateConfig) => {
    // Abrir vista previa del certificado
    console.log("[v0] Abriendo vista previa para:", config.nombre)
    window.open(`/preview-certificate?config_id=${config.id}`, "_blank")
  }

  const handleDuplicate = (config: CertificateConfig) => {
    const duplicated = {
      ...config,
      id: undefined,
      nombre: `${config.nombre} (Copia)`,
      es_plantilla_defecto: false,
    }
    setFormData(duplicated)
    setSelectedConfig(null)
    setIsEditing(true)
  }

  return (
    <div className="container mx-auto p-6 space-y-6">
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-3xl font-bold text-gray-900 mb-2">Configuración de Certificados</h1>
          <p className="text-gray-600">Personaliza el formato y contenido de los certificados para cada escuela</p>
        </div>
        <Button onClick={handleNew} className="bg-red-600 hover:bg-red-700">
          <FileText className="w-4 h-4 mr-2" />
          Nueva Configuración
        </Button>
      </div>

      {message && (
        <Alert className="border-green-200 bg-green-50">
          <AlertCircle className="h-4 w-4 text-green-600" />
          <AlertDescription className="text-green-800">{message}</AlertDescription>
        </Alert>
      )}

      {error && (
        <Alert className="border-red-200 bg-red-50">
          <AlertCircle className="h-4 w-4 text-red-600" />
          <AlertDescription className="text-red-800">{error}</AlertDescription>
        </Alert>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Lista de configuraciones */}
        <div className="lg:col-span-1">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center">
                <Settings className="w-5 h-5 mr-2" />
                Configuraciones Disponibles
              </CardTitle>
              <CardDescription>Selecciona una configuración para ver detalles o crear una nueva</CardDescription>
            </CardHeader>
            <CardContent>
              {configs.length === 0 ? (
                <div className="text-center py-8 text-gray-500">
                  <FileText className="w-12 h-12 mx-auto mb-4 text-gray-300" />
                  <p>No hay configuraciones disponibles</p>
                  <Button variant="outline" onClick={handleNew} className="mt-4 bg-transparent">
                    Crear primera configuración
                  </Button>
                </div>
              ) : (
                <div className="space-y-3">
                  {configs.map((config) => (
                    <div
                      key={config.id}
                      className={`border rounded-lg p-4 cursor-pointer transition-colors ${
                        selectedConfig?.id === config.id
                          ? "border-red-300 bg-red-50"
                          : "border-gray-200 hover:bg-gray-50"
                      }`}
                      onClick={() => setSelectedConfig(config)}
                    >
                      <div className="flex justify-between items-start mb-2">
                        <h4 className="font-semibold text-gray-900">{config.nombre}</h4>
                        {config.es_plantilla_defecto && (
                          <Badge variant="secondary" className="text-xs">
                            Por defecto
                          </Badge>
                        )}
                      </div>
                      <p className="text-sm text-gray-600 mb-3">{config.descripcion || "Sin descripción"}</p>
                      <div className="flex justify-between items-center">
                        <div className="text-xs text-gray-500">
                          {config.escuela_id
                            ? schools.find((s) => s.id === config.escuela_id)?.nombre || "Escuela no encontrada"
                            : "Todas las escuelas"}
                        </div>
                        <div className="flex space-x-1">
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={(e) => {
                              e.stopPropagation()
                              handleEdit(config)
                            }}
                          >
                            Editar
                          </Button>
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={(e) => {
                              e.stopPropagation()
                              handlePreview(config)
                            }}
                          >
                            <Eye className="w-4 h-4" />
                          </Button>
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={(e) => {
                              e.stopPropagation()
                              handleDuplicate(config)
                            }}
                          >
                            <Copy className="w-4 h-4" />
                          </Button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </CardContent>
          </Card>
        </div>

        {/* Panel de configuración */}
        <div className="lg:col-span-2">
          {isEditing ? (
            <Card>
              <CardHeader>
                <CardTitle>{selectedConfig ? "Editar Configuración" : "Nueva Configuración"}</CardTitle>
                <CardDescription>Personaliza todos los aspectos del certificado</CardDescription>
              </CardHeader>
              <CardContent>
                <Tabs defaultValue="general" className="space-y-6">
                  <TabsList className="grid w-full grid-cols-4">
                    <TabsTrigger value="general">General</TabsTrigger>
                    <TabsTrigger value="textos">Textos</TabsTrigger>
                    <TabsTrigger value="firmas">Firmas</TabsTrigger>
                    <TabsTrigger value="diseño">Diseño</TabsTrigger>
                  </TabsList>

                  <TabsContent value="general" className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <Label htmlFor="nombre">Nombre de la Configuración *</Label>
                        <Input
                          id="nombre"
                          value={formData.nombre}
                          onChange={(e) => setFormData((prev) => ({ ...prev, nombre: e.target.value }))}
                          placeholder="Ej: Configuración ESIBOC 2025"
                        />
                      </div>
                      <div>
                        <Label htmlFor="escuela">Escuela</Label>
                        <Select
                          value={formData.escuela_id?.toString() || "0"}
                          onValueChange={(value) =>
                            setFormData((prev) => ({
                              ...prev,
                              escuela_id: value ? Number.parseInt(value) : undefined,
                            }))
                          }
                        >
                          <SelectTrigger>
                            <SelectValue placeholder="Seleccionar escuela (opcional)" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="0">Todas las escuelas</SelectItem>
                            {schools.map((school) => (
                              <SelectItem key={school.id} value={school.id.toString()}>
                                {school.nombre} ({school.codigo})
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </div>
                    </div>

                    <div>
                      <Label htmlFor="descripcion">Descripción</Label>
                      <Textarea
                        id="descripcion"
                        value={formData.descripcion}
                        onChange={(e) => setFormData((prev) => ({ ...prev, descripcion: e.target.value }))}
                        placeholder="Describe el propósito de esta configuración"
                        rows={3}
                      />
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <Label htmlFor="numero_registro_base">Número de Registro Base</Label>
                        <Input
                          id="numero_registro_base"
                          value={formData.numero_registro_base}
                          onChange={(e) => setFormData((prev) => ({ ...prev, numero_registro_base: e.target.value }))}
                          placeholder="Ej: 184-2025"
                        />
                      </div>
                      <div>
                        <Label htmlFor="formato_consecutivo">Formato Consecutivo</Label>
                        <Input
                          id="formato_consecutivo"
                          value={formData.formato_consecutivo}
                          onChange={(e) => setFormData((prev) => ({ ...prev, formato_consecutivo: e.target.value }))}
                          placeholder="{año}-{registro_curso}-{orden_alfabetico}"
                        />
                      </div>
                    </div>

                    <div className="flex items-center space-x-2">
                      <Switch
                        id="es_plantilla_defecto"
                        checked={formData.es_plantilla_defecto}
                        onCheckedChange={(checked) =>
                          setFormData((prev) => ({ ...prev, es_plantilla_defecto: checked }))
                        }
                      />
                      <Label htmlFor="es_plantilla_defecto">Usar como plantilla por defecto</Label>
                    </div>
                  </TabsContent>

                  <TabsContent value="textos" className="space-y-4">
                    <div className="space-y-4">
                      <div>
                        <Label htmlFor="texto_certifica_que">Texto "Certifica que"</Label>
                        <Input
                          id="texto_certifica_que"
                          value={formData.texto_certifica_que}
                          onChange={(e) => setFormData((prev) => ({ ...prev, texto_certifica_que: e.target.value }))}
                        />
                      </div>

                      <div>
                        <Label htmlFor="texto_curso_autorizado">Texto "Curso autorizado"</Label>
                        <Textarea
                          id="texto_curso_autorizado"
                          value={formData.texto_curso_autorizado}
                          onChange={(e) => setFormData((prev) => ({ ...prev, texto_curso_autorizado: e.target.value }))}
                          rows={2}
                        />
                      </div>

                      <div>
                        <Label htmlFor="texto_bajo_acta">Texto "Bajo acta"</Label>
                        <Textarea
                          id="texto_bajo_acta"
                          value={formData.texto_bajo_acta}
                          onChange={(e) => setFormData((prev) => ({ ...prev, texto_bajo_acta: e.target.value }))}
                          rows={2}
                        />
                      </div>

                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <Label htmlFor="texto_duracion">Texto "Duración"</Label>
                          <Input
                            id="texto_duracion"
                            value={formData.texto_duracion}
                            onChange={(e) => setFormData((prev) => ({ ...prev, texto_duracion: e.target.value }))}
                          />
                        </div>
                        <div>
                          <Label htmlFor="texto_identificado_con">Texto "Identificado con"</Label>
                          <Input
                            id="texto_identificado_con"
                            value={formData.texto_identificado_con}
                            onChange={(e) =>
                              setFormData((prev) => ({ ...prev, texto_identificado_con: e.target.value }))
                            }
                          />
                        </div>
                      </div>

                      <div>
                        <Label htmlFor="texto_realizado_en">Texto "Realizado en"</Label>
                        <Textarea
                          id="texto_realizado_en"
                          value={formData.texto_realizado_en}
                          onChange={(e) => setFormData((prev) => ({ ...prev, texto_realizado_en: e.target.value }))}
                          rows={2}
                        />
                      </div>

                      <div>
                        <Label htmlFor="texto_constancia">Texto "Constancia"</Label>
                        <Textarea
                          id="texto_constancia"
                          value={formData.texto_constancia}
                          onChange={(e) => setFormData((prev) => ({ ...prev, texto_constancia: e.target.value }))}
                          rows={2}
                        />
                      </div>
                    </div>
                  </TabsContent>

                  <TabsContent value="firmas" className="space-y-4">
                    <div className="space-y-4">
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <Label htmlFor="director_nacional_nombre">Director Nacional - Nombre</Label>
                          <Input
                            id="director_nacional_nombre"
                            value={formData.director_nacional_nombre}
                            onChange={(e) =>
                              setFormData((prev) => ({ ...prev, director_nacional_nombre: e.target.value }))
                            }
                          />
                        </div>
                        <div>
                          <Label htmlFor="director_nacional_cargo">Director Nacional - Cargo</Label>
                          <Input
                            id="director_nacional_cargo"
                            value={formData.director_nacional_cargo}
                            onChange={(e) =>
                              setFormData((prev) => ({ ...prev, director_nacional_cargo: e.target.value }))
                            }
                          />
                        </div>
                      </div>

                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          <Label htmlFor="comandante_nombre">Comandante - Nombre</Label>
                          <Input
                            id="comandante_nombre"
                            value={formData.comandante_nombre}
                            onChange={(e) => setFormData((prev) => ({ ...prev, comandante_nombre: e.target.value }))}
                          />
                        </div>
                        <div>
                          <Label htmlFor="comandante_cargo">Comandante - Cargo</Label>
                          <Input
                            id="comandante_cargo"
                            value={formData.comandante_cargo}
                            onChange={(e) => setFormData((prev) => ({ ...prev, comandante_cargo: e.target.value }))}
                          />
                        </div>
                      </div>

                      <div className="space-y-3">
                        <h4 className="font-semibold">Firmas a mostrar</h4>
                        <div className="flex items-center space-x-2">
                          <Switch
                            id="mostrar_firma_director_nacional"
                            checked={formData.mostrar_firma_director_nacional}
                            onCheckedChange={(checked) =>
                              setFormData((prev) => ({ ...prev, mostrar_firma_director_nacional: checked }))
                            }
                          />
                          <Label htmlFor="mostrar_firma_director_nacional">Mostrar firma del Director Nacional</Label>
                        </div>

                        <div className="flex items-center space-x-2">
                          <Switch
                            id="mostrar_firma_director_escuela"
                            checked={formData.mostrar_firma_director_escuela}
                            onCheckedChange={(checked) =>
                              setFormData((prev) => ({ ...prev, mostrar_firma_director_escuela: checked }))
                            }
                          />
                          <Label htmlFor="mostrar_firma_director_escuela">Mostrar firma del Director de Escuela</Label>
                        </div>

                        <div className="flex items-center space-x-2">
                          <Switch
                            id="mostrar_firma_coordinador"
                            checked={formData.mostrar_firma_coordinador}
                            onCheckedChange={(checked) =>
                              setFormData((prev) => ({ ...prev, mostrar_firma_coordinador: checked }))
                            }
                          />
                          <Label htmlFor="mostrar_firma_coordinador">Mostrar firma del Coordinador</Label>
                        </div>
                      </div>
                    </div>
                  </TabsContent>

                  <TabsContent value="diseño" className="space-y-4">
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div>
                        <Label htmlFor="orientacion">Orientación</Label>
                        <Select
                          value={formData.orientacion}
                          onValueChange={(value: "vertical" | "horizontal") =>
                            setFormData((prev) => ({ ...prev, orientacion: value }))
                          }
                        >
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="vertical">Vertical (Retrato)</SelectItem>
                            <SelectItem value="horizontal">Horizontal (Paisaje)</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                      <div>
                        <Label htmlFor="tamaño_papel">Tamaño de Papel</Label>
                        <Select
                          value={formData.tamaño_papel}
                          onValueChange={(value) => setFormData((prev) => ({ ...prev, tamaño_papel: value }))}
                        >
                          <SelectTrigger>
                            <SelectValue />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="A4">A4</SelectItem>
                            <SelectItem value="Letter">Carta</SelectItem>
                            <SelectItem value="Legal">Legal</SelectItem>
                          </SelectContent>
                        </Select>
                      </div>
                    </div>

                    <div className="space-y-4">
                      <h4 className="font-semibold">Encabezados</h4>
                      <div>
                        <Label htmlFor="texto_encabezado_izquierdo">Encabezado Izquierdo</Label>
                        <Textarea
                          id="texto_encabezado_izquierdo"
                          value={formData.texto_encabezado_izquierdo}
                          onChange={(e) =>
                            setFormData((prev) => ({ ...prev, texto_encabezado_izquierdo: e.target.value }))
                          }
                          rows={2}
                        />
                      </div>
                      <div>
                        <Label htmlFor="texto_encabezado_centro">Encabezado Centro</Label>
                        <Textarea
                          id="texto_encabezado_centro"
                          value={formData.texto_encabezado_centro}
                          onChange={(e) =>
                            setFormData((prev) => ({ ...prev, texto_encabezado_centro: e.target.value }))
                          }
                          rows={3}
                        />
                      </div>
                    </div>

                    <div className="space-y-3">
                      <h4 className="font-semibold">Contenido Programático</h4>
                      <div className="flex items-center space-x-2">
                        <Switch
                          id="mostrar_contenido_programatico"
                          checked={formData.mostrar_contenido_programatico}
                          onCheckedChange={(checked) =>
                            setFormData((prev) => ({ ...prev, mostrar_contenido_programatico: checked }))
                          }
                        />
                        <Label htmlFor="mostrar_contenido_programatico">Incluir contenido programático</Label>
                      </div>

                      {formData.mostrar_contenido_programatico && (
                        <div>
                          <Label htmlFor="columnas_contenido">Número de Columnas</Label>
                          <Select
                            value={formData.columnas_contenido.toString()}
                            onValueChange={(value) =>
                              setFormData((prev) => ({ ...prev, columnas_contenido: Number.parseInt(value) }))
                            }
                          >
                            <SelectTrigger className="w-32">
                              <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                              <SelectItem value="1">1 Columna</SelectItem>
                              <SelectItem value="2">2 Columnas</SelectItem>
                              <SelectItem value="3">3 Columnas</SelectItem>
                            </SelectContent>
                          </Select>
                        </div>
                      )}
                    </div>
                  </TabsContent>
                </Tabs>

                <div className="flex justify-end space-x-3 pt-6 border-t">
                  <Button variant="outline" onClick={handleCancel}>
                    Cancelar
                  </Button>
                  <Button onClick={handleSave} disabled={isLoading} className="bg-red-600 hover:bg-red-700">
                    <Save className="w-4 h-4 mr-2" />
                    {isLoading ? "Guardando..." : "Guardar Configuración"}
                  </Button>
                </div>
              </CardContent>
            </Card>
          ) : selectedConfig ? (
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center justify-between">
                  <span>{selectedConfig.nombre}</span>
                  <div className="flex space-x-2">
                    <Button variant="outline" onClick={() => handleEdit(selectedConfig)}>
                      Editar
                    </Button>
                    <Button variant="outline" onClick={() => handlePreview(selectedConfig)}>
                      <Eye className="w-4 h-4 mr-2" />
                      Vista Previa
                    </Button>
                  </div>
                </CardTitle>
                <CardDescription>{selectedConfig.descripcion}</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-6">
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <Label className="text-sm font-medium text-gray-700">Escuela</Label>
                      <p className="text-gray-900">
                        {selectedConfig.escuela_id
                          ? schools.find((s) => s.id === selectedConfig.escuela_id)?.nombre || "Escuela no encontrada"
                          : "Todas las escuelas"}
                      </p>
                    </div>
                    <div>
                      <Label className="text-sm font-medium text-gray-700">Registro Base</Label>
                      <p className="text-gray-900">{selectedConfig.numero_registro_base}</p>
                    </div>
                  </div>

                  <div>
                    <Label className="text-sm font-medium text-gray-700">Formato Consecutivo</Label>
                    <p className="text-gray-900 font-mono text-sm bg-gray-50 p-2 rounded">
                      {selectedConfig.formato_consecutivo}
                    </p>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                      <Label className="text-sm font-medium text-gray-700">Director Nacional</Label>
                      <Badge variant={selectedConfig.mostrar_firma_director_nacional ? "default" : "secondary"}>
                        {selectedConfig.mostrar_firma_director_nacional ? "Mostrar" : "Ocultar"}
                      </Badge>
                    </div>
                    <div>
                      <Label className="text-sm font-medium text-gray-700">Director Escuela</Label>
                      <Badge variant={selectedConfig.mostrar_firma_director_escuela ? "default" : "secondary"}>
                        {selectedConfig.mostrar_firma_director_escuela ? "Mostrar" : "Ocultar"}
                      </Badge>
                    </div>
                    <div>
                      <Label className="text-sm font-medium text-gray-700">Coordinador</Label>
                      <Badge variant={selectedConfig.mostrar_firma_coordinador ? "default" : "secondary"}>
                        {selectedConfig.mostrar_firma_coordinador ? "Mostrar" : "Ocultar"}
                      </Badge>
                    </div>
                  </div>

                  <div>
                    <Label className="text-sm font-medium text-gray-700">Autoridades</Label>
                    <div className="bg-gray-50 p-4 rounded-lg space-y-2">
                      <p>
                        <strong>Director Nacional:</strong> {selectedConfig.director_nacional_nombre}
                      </p>
                      <p className="text-sm text-gray-600">{selectedConfig.director_nacional_cargo}</p>
                      <p>
                        <strong>Comandante:</strong> {selectedConfig.comandante_nombre}
                      </p>
                      <p className="text-sm text-gray-600">{selectedConfig.comandante_cargo}</p>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          ) : (
            <Card>
              <CardContent className="flex flex-col items-center justify-center py-12">
                <Settings className="w-16 h-16 text-gray-300 mb-4" />
                <h3 className="text-lg font-semibold text-gray-900 mb-2">Selecciona una configuración</h3>
                <p className="text-gray-600 text-center mb-6">
                  Haz clic en una configuración de la lista para ver sus detalles o crear una nueva
                </p>
                <Button onClick={handleNew} className="bg-red-600 hover:bg-red-700">
                  <FileText className="w-4 h-4 mr-2" />
                  Nueva Configuración
                </Button>
              </CardContent>
            </Card>
          )}
        </div>
      </div>
    </div>
  )
}
