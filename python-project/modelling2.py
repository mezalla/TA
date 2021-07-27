import pandas as pd
import numpy as np
import math
import re
import mysql.connector
from mysql.connector import Error
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report
from sklearn.metrics import confusion_matrix
from sklearn.metrics import accuracy_score
from sklearn.metrics import mean_absolute_error
import matplotlib.pyplot as plt
from sklearn.metrics import plot_confusion_matrix

#----------------------------- IMPORT DATA ---------------------------------------

#isi list ke dataframe
tweet_list= [] # Lists to be added as columns( Tweets Text) in our dataframe
label_list = []

def get_mysql():
    try:
        connection = mysql.connector.connect(host='localhost',
                                             database='bismillah',
                                             user='root',
                                             password='',
                                             charset='utf8')
        
        sql_select_Query = "select * from prepro where label = 'fake' limit 150"
        cursor = connection.cursor()
        cursor.execute(sql_select_Query)
        # get all records
        records = cursor.fetchall()
        # print("Total number of rows in table: ", cursor.rowcount)

        # print("\nPrinting each row")

    except mysql.connector.Error as e:
        print("Error reading data from MySQL table", e)
    finally:
        if connection.is_connected():
            connection.close()
            cursor.close()
            # print("MySQL connection is closed")

            return records
        
def get_mysql2():
    try:
        connection = mysql.connector.connect(host='localhost',
                                             database='bismillah',
                                             user='root',
                                             password='',
                                             charset='utf8')

        sql_select_Query = "select * from prepro where label = 'true' limit 150"
        cursor = connection.cursor()
        cursor.execute(sql_select_Query)
        # get all records
        records = cursor.fetchall()
        # print("Total number of rows in table: ", cursor.rowcount)

        # print("\nPrinting each row")

    except mysql.connector.Error as e:
        print("Error reading data from MySQL table", e)
    finally:
        if connection.is_connected():
            connection.close()
            cursor.close()
            # print("MySQL connection is closed")

            return records
data_twitter = get_mysql()

for row in data_twitter:
    tweet_list.append(row[1])
    label_list.append(row[2])

data_twitter = get_mysql2()

for row in data_twitter:
    tweet_list.append(row[1])
    label_list.append(row[2])

data = pd.DataFrame({'tweet':tweet_list,
                    'label':label_list})


# Pembagian data training & testing

x_train, x_test, y_train, y_test = train_test_split(data['tweet'], data['label'], train_size=0.8, 
                                                    test_size=0.2)

train_data = pd.DataFrame({'tweet':x_train, 'label':y_train})
test_data = pd.DataFrame({'tweet':x_test, 'label':y_test})

train_data.reset_index(drop=True, inplace=True)
test_data.reset_index(drop=True, inplace=True)

# Ukuran data training atau total seluruh kelas
print('Ukuran data train:', train_data.shape)
n_train = train_data.shape[0]

test_data['label'].value_counts()
train_data['label'].value_counts(ascending=True)

kelas_true = train_data['label'].value_counts()['true']
kelas_fake = train_data['label'].value_counts()['fake']

#----------------------------- FUNCTION ---------------------------------------

def tokenisasi(tweet):
    
    # Tokenisasi
    token = re.findall('[A-Za-z]+', tweet)
    token = np.array(token)
    
    return token

def preprocessing(tweet):
    # Tokenisasi
    token = tokenisasi(tweet)
    
    return token

def getFitur(dokumen):
    fitur = [preprocessing(tweet) for tweet in dokumen]
    fitur = [term for listTerm in fitur for term in listTerm]
    fitur = np.unique(fitur)
    return fitur

def term_presence(dokumen):
    # get Fitur
    fitur = getFitur(dokumen)
    
    # Term Pressence Table
    zero_data = np.zeros((dokumen.shape[0], fitur.shape[0]))
    tf = pd.DataFrame(zero_data, columns=fitur)
    
    for i, tweet in enumerate(dokumen):
        kata = preprocessing(tweet)
        # Term presence        
        temp = np.where(np.isin(fitur, kata), 1, 0)        
        
        tf.iloc[i] = temp
        
    return tf

def get_percentage(dokumen, percent):
    start = 0
    end = int(len(dokumen) * percent)

    return dokumen.iloc[start:end]

def entropy(rows, n_term, kelas):
    temp = 0
    
    for i, val in enumerate(kelas):
        temp += -(rows[i]/kelas[i] * 
                  np.where(np.isinf(np.log(rows[i]/kelas[i])), 0, np.log(rows[i]/kelas[i])))
    
    value = rows.iloc[-1] / n_term * temp
    
    return value

def information_gain(dokumen_1, dokumen_0, *kelas):
    # Entropy total
    entropy_total = 0
    
    for i, val in enumerate(kelas):
        entropy_total -= val / sum(kelas) * np.where(np.isinf(np.log(val / sum(kelas))), 
                                                     0, 
                                                     np.log(val / sum(kelas)))
    
    jumlah_term = dokumen_1.iloc[:, -1].sum()  
    
    s1_entropy = []
    s0_entropy = []
    
    idx = 0
    # dokumen nilai 1
    for i in dokumen_1.iterrows():
        s1_entropy.append(entropy(i[1], jumlah_term, kelas))
        idx += 1
        
    # Dokumen nilai 0
    for i in dokumen_0.iterrows():
        s0_entropy.append(entropy(i[1], jumlah_term, kelas))

    # Convert to array matrix 
    s1_entropy = np.array(s1_entropy)
    s0_entropy = np.array(s0_entropy)
    
    information_gain = entropy_total - s1_entropy + s0_entropy

    # return as Pandas dataframe
    ig = pd.DataFrame(dokumen_1.index, columns=["Term"])    
    ig['IG'] = information_gain

    return ig

def posterior(dokumen, n_term):
    for i, val in enumerate(dokumen.iloc[:, 1:].iteritems()):
        dokumen[i] = (val[1] + 1) / (val[1].sum() + n_term)        
        
    return dokumen

def prior(kelas):
    prior = []
    
    for i in kelas:
        prior.append(i / sum(kelas))
        
    return prior
    
def multinomial_naive_bayes(term, doc_uji, *kelas):
    n_total = term.shape[0]
    
    """    
    Hitung posterior dan prior
    """
    post = posterior(term, n_total)
    pr = prior(kelas)
    
    """    
    Cek term data_uji pada data posterior
    """
    data = []
    for val in data_uji:
        data.append(term[term['Term'].isin(val)])

    """    
    Perhitungan likelihood
    """
    result = []
    temp = []
    for i, val in enumerate(data):
        if (len(temp) > 0):
            result.extend(np.array([temp]))
                          
        temp = []
        
        # If data = empty Dataframe, cukup dengan priornya
        if (data[i].empty):
            result.extend(np.array([pr]))        
        else:
            for idx, value in data[i].iloc[:, 1:].iteritems():
                #print("\nValue : {} * {} = {}\n".format(value.prod(), pr[idx], value.prod() * pr[idx]))
                temp.append(value.prod() * pr[idx])
                #print("\nTemp about to append : \n", temp)
            
    if (len(temp) > 0):
        result.extend(np.array([temp]))
    
    """        
    Menentukan kelas
    """ 
    kelas = []
    for i in result:
        kelas.append(np.argmax(i))
    
    """        
    Convert to dataframe    
    """ 
    result = pd.DataFrame(kelas, columns=["prediksi"])
    result["prediksi"] = result["prediksi"].map({0:'fake', 1:'true'})    

    return result


#----------------------------- FUNCTION ---------------------------------------

# Get test_data fitur
data_uji = [preprocessing(tweet) for tweet in test_data.tweet]
binary_tf = term_presence(train_data.tweet)
tf = pd.concat([binary_tf, train_data.label], axis=1)

#----------------------------- FUNCTION ---------------------------------------

# Hitung jumlah nilai 1
fake_1 = tf[tf.label == 'fake'].sum()
true_1 = tf[tf.label == 'true'].sum()
total_1 = tf.sum(axis=0)

tf_1 = pd.concat([fake_1, true_1, total_1], axis=1)
# Drop label
tf_1 = tf_1[:-1]

# Hitung jumlah nilai 0
fake_0 = (tf[tf.label == 'fake'] == 0).sum()
true_0 = (tf[tf.label == 'true'] == 0).sum()
tf_0 = pd.concat([fake_0, true_0], axis=1)

# Hitung total
tf_0[tf_0.shape[1]] = tf_0.sum(axis=1)
# Drop label
tf_0 = tf_0[:-1]

#----------------------------- FUNCTION ---------------------------------------

ig = information_gain(tf_1, tf_0, kelas_fake, kelas_true)

# Sorted IG
ig = ig.sort_values(by=["IG", "Term"], ascending=[False, True]).reset_index(drop=True)

# Information Gain threshold
ig_100 = ig
ig_90 = get_percentage(ig, .9)
ig_80 = get_percentage(ig, .8)
ig_70 = get_percentage(ig, .7)
ig_60 = get_percentage(ig, .6)
ig_50 = get_percentage(ig, .5)
ig_40 = get_percentage(ig, .4)
ig_30 = get_percentage(ig, .3)
ig_20 = get_percentage(ig, .2)
ig_10 = get_percentage(ig, .1)

# get term table (Term = 100%)
df_100 = tf_1[tf_1.index.isin(ig_100['Term'])].rename_axis('Term').reset_index()
df_90 = tf_1[tf_1.index.isin(ig_90['Term'])].rename_axis('Term').reset_index()
df_80 = tf_1[tf_1.index.isin(ig_80['Term'])].rename_axis('Term').reset_index()
df_70 = tf_1[tf_1.index.isin(ig_70['Term'])].rename_axis('Term').reset_index()
df_60 = tf_1[tf_1.index.isin(ig_60['Term'])].rename_axis('Term').reset_index()
df_50 = tf_1[tf_1.index.isin(ig_50['Term'])].rename_axis('Term').reset_index()
df_40 = tf_1[tf_1.index.isin(ig_40['Term'])].rename_axis('Term').reset_index()
df_30 = tf_1[tf_1.index.isin(ig_30['Term'])].rename_axis('Term').reset_index()
df_20 = tf_1[tf_1.index.isin(ig_20['Term'])].rename_axis('Term').reset_index()
df_10 = tf_1[tf_1.index.isin(ig_10['Term'])].rename_axis('Term').reset_index()

# drop total column
# df_100 = df_100.drop(df_100.columns[len(df_100.columns)-1], axis=1)
# df_90 = df_90.drop(df_90.columns[len(df_90.columns)-1], axis=1)
# df_80 = df_80.drop(df_80.columns[len(df_80.columns)-1], axis=1)
# df_70 = df_70.drop(df_70.columns[len(df_70.columns)-1], axis=1)
# df_60 = df_60.drop(df_60.columns[len(df_60.columns)-1], axis=1)
df_50 = df_50.drop(df_50.columns[len(df_50.columns)-1], axis=1)
# df_40 = df_40.drop(df_40.columns[len(df_40.columns)-1], axis=1)
# df_30 = df_30.drop(df_30.columns[len(df_30.columns)-1], axis=1)
# df_20 = df_20.drop(df_20.columns[len(df_20.columns)-1], axis=1)
# df_10 = df_10.drop(df_10.columns[len(df_10.columns)-1], axis=1)

# classification_100 = multinomial_naive_bayes(df_100, data_uji, kelas_fake, kelas_true)
# classification_90 = multinomial_naive_bayes(df_90, data_uji, kelas_fake, kelas_true)
# classification_80 = multinomial_naive_bayes(df_80, data_uji, kelas_fake, kelas_true)
# classification_70 = multinomial_naive_bayes(df_70, data_uji, kelas_fake, kelas_true)
# classification_60 = multinomial_naive_bayes(df_60, data_uji, kelas_fake, kelas_true)
classification_50 = multinomial_naive_bayes(df_50, data_uji, kelas_fake, kelas_true)
# classification_40 = multinomial_naive_bayes(df_40, data_uji, kelas_fake, kelas_true)
# classification_30 = multinomial_naive_bayes(df_30, data_uji, kelas_fake, kelas_true)
# classification_20 = multinomial_naive_bayes(df_20, data_uji, kelas_fake, kelas_true)
# classification_10 = multinomial_naive_bayes(df_10, data_uji, kelas_fake, kelas_true)

klasifikasi = pd.concat([test_data.tweet, test_data.label, classification_50], axis=1)

print(klasifikasi)

# print('\nClasification report 100% :\n', classification_report(test_data['label'].reset_index(drop=True),
#                                                          classification_100))

# print('\nClasification report 90% :\n', classification_report(test_data['label'].reset_index(drop=True),
#                                                          classification_90))

# print('\nClasification report 80% :\n', classification_report(test_data['label'].reset_index(drop=True),
#                                                          classification_80))

# print('\nClasification report 70% :\n', classification_report(test_data['label'].reset_index(drop=True),
#                                                          classification_70))

# print('\nClasification report 60% :\n', classification_report(test_data['label'].reset_index(drop=True),
#                                                          classification_60))

print('\nClasification report 50% :\n', classification_report(test_data['label'].reset_index(drop=True),
                                                          classification_50))

# print('\nClasification report 40% :\n', classification_report(test_data['label'].reset_index(drop=True),
#                                                          classification_40))

# print('\nClasification report 30% :\n', classification_report(test_data['label'].reset_index(drop=True),
#                                                          classification_30))

# print('\nClasification report 20% :\n', classification_report(test_data['label'].reset_index(drop=True),
#                                                          classification_20))

# print('\nClasification report 10% :\n', classification_report(test_data['label'].reset_index(drop=True),
#                                                          classification_10))