import 'package:intl/intl.dart';

/// Formatte un montant entier en FCFA, ex: 150000 -> "150 000 FCFA".
String formatPrice(num amount) {
  final formatter = NumberFormat.decimalPattern('fr_FR');
  return '${formatter.format(amount)} FCFA';
}

/// Date relative simple en français (ex: "il y a 2 h", "hier", "12 mars").
String formatRelativeDate(DateTime date) {
  final now = DateTime.now();
  final diff = now.difference(date);

  if (diff.inMinutes < 1) return "à l'instant";
  if (diff.inMinutes < 60) return 'il y a ${diff.inMinutes} min';
  if (diff.inHours < 24) return 'il y a ${diff.inHours} h';
  if (diff.inDays == 1) return 'hier';
  if (diff.inDays < 7) return 'il y a ${diff.inDays} j';

  return DateFormat('d MMM yyyy', 'fr_FR').format(date);
}

const productConditionLabels = {
  'neuf': 'Neuf',
  'tres_bon': 'Très bon état',
  'bon': 'Bon état',
  'moyen': 'État moyen',
};

const reportReasonLabels = {
  'produit_frauduleux': 'Produit frauduleux',
  'faux_produit': 'Faux produit',
  'prix_trompeur': 'Prix trompeur',
  'vendeur_suspect': 'Vendeur suspect',
  'annonce_incorrecte': 'Annonce incorrecte',
  'harcelement': 'Harcèlement',
  'arnaque': 'Arnaque',
  'autre': 'Autre',
};
