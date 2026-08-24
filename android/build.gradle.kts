allprojects {
    repositories {
        google()
        mavenCentral()
        // Miroir officiel Google de Maven Central : utile quand
        // repo.maven.apache.org est injoignable (DNS/réseau) alors que
        // l'infrastructure Google (dl.google.com) fonctionne, elle.
        maven { url = uri("https://maven-central.storage-download.googleapis.com/maven2/") }
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
