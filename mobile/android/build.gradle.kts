allprojects {
    repositories {
        google()
        // Miroir officiel Google de Maven Central en premier : repo.maven.apache.org
        // s'est révélé injoignable (DNS/réseau) par intermittence sur certains
        // réseaux, alors que l'infrastructure Google (dl.google.com) fonctionne.
        maven { url = uri("https://maven-central.storage-download.googleapis.com/maven2/") }
        mavenCentral()
    }
}

val newBuildDir: Directory =
    rootProject.layout.buildDirectory
        .dir("../../build")
        .get()
rootProject.layout.buildDirectory.value(newBuildDir)

subprojects {
    val newSubprojectBuildDir: Directory = newBuildDir.dir(project.name)
    project.layout.buildDirectory.value(newSubprojectBuildDir)
}
subprojects {
    project.evaluationDependsOn(":app")
}

tasks.register<Delete>("clean") {
    delete(rootProject.layout.buildDirectory)
}
